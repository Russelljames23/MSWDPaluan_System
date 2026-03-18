<?php
class User
{
    private $conn;
    private $table_name = "users";

    public $id;
    public $lastname;
    public $firstname;
    public $middlename;
    public $birthdate;
    public $gender;
    public $email;
    public $contact_no;
    public $address;
    public $username;
    public $password;
    public $user_type;
    public $status;
    public $created_by;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create()
    {
        try {
            error_log("User::create() called for user: " . $this->username);

            $query = "INSERT INTO " . $this->table_name . " 
        SET lastname=:lastname, firstname=:firstname, middlename=:middlename, 
            birthdate=:birthdate, gender=:gender, email=:email, contact_no=:contact_no, 
            address=:address, username=:username, password=:password, 
            user_type=:user_type, status='active', 
            created_by=:created_by, 
            created_at=NOW()";

            $stmt = $this->conn->prepare($query);

            if (!$stmt) {
                $errorInfo = $this->conn->errorInfo();
                error_log("Prepare failed: " . print_r($errorInfo, true));
                return false;
            }

            // Sanitize
            $this->lastname = htmlspecialchars(strip_tags($this->lastname));
            $this->firstname = htmlspecialchars(strip_tags($this->firstname));
            $this->middlename = htmlspecialchars(strip_tags($this->middlename));
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->username = htmlspecialchars(strip_tags($this->username));

            // Hash password
            $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

            error_log("Creating user: " . $this->username . ", Email: " . $this->email);

            // Bind parameters
            $stmt->bindParam(":lastname", $this->lastname);
            $stmt->bindParam(":firstname", $this->firstname);
            $stmt->bindParam(":middlename", $this->middlename);
            $stmt->bindParam(":birthdate", $this->birthdate);
            $stmt->bindParam(":gender", $this->gender);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":contact_no", $this->contact_no);
            $stmt->bindParam(":address", $this->address);
            $stmt->bindParam(":username", $this->username);
            $stmt->bindParam(":password", $password_hash);
            $stmt->bindParam(":user_type", $this->user_type);

            // Handle created_by - allow NULL
            if (empty($this->created_by)) {
                $stmt->bindValue(":created_by", null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(":created_by", $this->created_by);
            }

            // Execute
            $result = $stmt->execute();

            if ($result) {
                $this->id = $this->conn->lastInsertId();
                error_log("✓ User created successfully. ID: " . $this->id);
                return true;
            } else {
                $errorInfo = $stmt->errorInfo();

                // Check if it's a duplicate entry error (user was actually created)
                if (isset($errorInfo[1]) && $errorInfo[1] == 1062) {
                    // Duplicate entry - user already exists
                    error_log("Duplicate entry error. Checking if user exists...");

                    // Try to get the existing user ID
                    $checkStmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                    $checkStmt->execute([$this->username, $this->email]);
                    $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

                    if ($existingUser) {
                        $this->id = $existingUser['id'];
                        error_log("✓ User already exists with ID: " . $this->id);
                        return true; // Return true even though it's a duplicate
                    }
                }

                error_log("✗ Execute failed: " . print_r($errorInfo, true));
                return false;
            }
        } catch (PDOException $e) {
            error_log("PDO Exception in User::create: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");

            // Check if it's a duplicate entry
            if ($e->getCode() == 23000) { // SQLSTATE for integrity constraint violation
                error_log("Duplicate entry detected. Checking if user exists...");

                try {
                    $checkStmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                    $checkStmt->execute([$this->username, $this->email]);
                    $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

                    if ($existingUser) {
                        $this->id = $existingUser['id'];
                        error_log("✓ User already exists with ID: " . $this->id);
                        return true;
                    }
                } catch (Exception $checkError) {
                    error_log("Error checking for existing user: " . $checkError->getMessage());
                }
            }

            return false;
        } catch (Exception $e) {
            error_log("General Exception in User::create: " . $e->getMessage());
            return false;
        }
    }

    // Read all users
    public function read()
    {
        $query = "SELECT u.*, 
                 creator.firstname as creator_fname, 
                 creator.lastname as creator_lname 
                 FROM " . $this->table_name . " u 
                 LEFT JOIN users creator ON u.created_by = creator.id 
                 ORDER BY u.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Check if username exists
    public function usernameExists($exclude_id = null)
    {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username";
        if ($exclude_id) {
            $query .= " AND id != :exclude_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $this->username);
        if ($exclude_id) {
            $stmt->bindParam(":exclude_id", $exclude_id);
        }
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Check if email exists
    public function emailExists($exclude_id = null)
    {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        if ($exclude_id) {
            $query .= " AND id != :exclude_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        if ($exclude_id) {
            $stmt->bindParam(":exclude_id", $exclude_id);
        }
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Update user
    public function update()
    {
        $query = "UPDATE " . $this->table_name . " 
                SET lastname=:lastname, firstname=:firstname, middlename=:middlename, 
                    birthdate=:birthdate, gender=:gender, email=:email, contact_no=:contact_no, 
                    address=:address, username=:username, user_type=:user_type, status=:status 
                WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->lastname = htmlspecialchars(strip_tags($this->lastname));
        $this->firstname = htmlspecialchars(strip_tags($this->firstname));
        $this->middlename = htmlspecialchars(strip_tags($this->middlename));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->username = htmlspecialchars(strip_tags($this->username));

        // Bind parameters
        $stmt->bindParam(":lastname", $this->lastname);
        $stmt->bindParam(":firstname", $this->firstname);
        $stmt->bindParam(":middlename", $this->middlename);
        $stmt->bindParam(":birthdate", $this->birthdate);
        $stmt->bindParam(":gender", $this->gender);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":contact_no", $this->contact_no);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":user_type", $this->user_type);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Delete user
    public function delete()
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        return $stmt->execute();
    }
}
