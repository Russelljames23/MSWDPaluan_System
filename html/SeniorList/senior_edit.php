<?php
// senior_edit.php
require_once "../../php/login/admin_header.php";

// Database configuration
$servername = "localhost";
$dbname = "u401132124_mswd_seniors";
$username = "u401132124_mswdopaluan";
$password = "Mswdo_PaluanSystem23";

$pdo = null;
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// Fetch current user data
$user_id = $_SESSION['user_id'] ?? 0;
$user_data = [];

if ($user_id && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching user data: " . $e->getMessage());
        $user_data = [];
    }
}

// Prepare full name
$full_name = '';
if (!empty($user_data['firstname']) && !empty($user_data['lastname'])) {
    $full_name = $user_data['firstname'] . ' ' . $user_data['lastname'];
    if (!empty($user_data['middlename'])) {
        $full_name = $user_data['firstname'] . ' ' . $user_data['middlename'] . ' ' . $user_data['lastname'];
    }
}

// Get profile photo URL
$profile_photo_url = '';
if (!empty($user_data['profile_photo'])) {
    $profile_photo_url = '../../' . $user_data['profile_photo'];
    if (!file_exists($profile_photo_url)) {
        $profile_photo_url = '';
    }
}

// Fallback to avatar if no profile photo
if (empty($profile_photo_url)) {
    $profile_photo_url = 'https://ui-avatars.com/api/?name=' . urlencode($full_name) . '&background=3b82f6&color=fff&size=128';
}

// Get applicant ID from URL
$applicant_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$ctx = urlencode($_GET['session_context'] ?? session_id());

if ($applicant_id <= 0) {
    header("Location: activelist.php");
    exit();
}

// Initialize senior data array
$senior_data = [];
$errors = [];
$success_message = '';

// Helper function to format date for HTML input
function formatDateForInput($date)
{
    if (empty($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
        return '';
    }
    return date('Y-m-d', strtotime($date));
}

// Helper function to get safe year value
function formatYearForInput($year)
{
    if (empty($year) || $year == 0 || $year == '0000') {
        return '';
    }
    return (int)$year;
}

// Helper function to get array value with trimming
function getArrayValue($array, $key, $default = '')
{
    if (is_array($array) && isset($array[$key]) && $array[$key] !== null) {
        return trim($array[$key]);
    }
    return $default;
}

// Form options - UPDATED living_arrangements to match your database values
$genders = ['Male', 'Female'];
$civil_statuses = ['Single', 'Married', 'Widowed', 'Separated', 'Divorced'];
$educational_levels = ['Elementary', 'High School', 'College', 'Vocational', 'Post-Graduate', 'None'];
// UPDATED: Changed to match your database values and form requirements
$living_arrangements = ['Owned', 'Living alone', 'Living with relatives', 'Rent'];
$status_options = ['Active', 'Inactive', 'Deceased'];
$validation_options = ['Validated', 'For Validation'];
$registration_statuses = ['Pending', 'Approved', 'Rejected', 'On Hold'];
$pension_sources = ['SSS', 'GSIS', 'PVAO', 'Private', 'Others'];
$income_sources = ['Pension', 'Work', 'Business', 'Family Support', 'Government Assistance', 'Others'];
$support_types = ['Cash', 'In-kind', 'Both'];

try {
    // Check if form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Start transaction
        $pdo->beginTransaction();

        // Validate and sanitize input
        $applicant_data = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'middle_name' => trim($_POST['middle_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'suffix' => trim($_POST['suffix'] ?? ''),
            'birth_date' => $_POST['birth_date'] ?? '',
            'birth_place' => trim($_POST['birth_place'] ?? ''),
            'gender' => $_POST['gender'] ?? '',
            'civil_status' => $_POST['civil_status'] ?? '',
            'religion' => trim($_POST['religion'] ?? ''),
            'citizenship' => trim($_POST['citizenship'] ?? ''),
            'educational_attainment' => $_POST['educational_attainment'] ?? '',
            'specialization_skills' => trim($_POST['specialization_skills'] ?? ''),
            'community_involvement' => trim($_POST['community_involvement'] ?? ''),
            'problems_needs' => trim($_POST['problems_needs'] ?? ''),
            'remarks' => trim($_POST['remarks'] ?? ''),
            'status' => $_POST['status'] ?? 'Active',
            'validation' => $_POST['validation'] ?? 'For Validation',
            'living_arrangement' => $_POST['living_arrangement'] ?? '',
            'contact_number' => trim($_POST['contact_number'] ?? ''),
            'date_of_death' => !empty($_POST['date_of_death']) ? $_POST['date_of_death'] : null,
            'inactive_reason' => trim($_POST['inactive_reason'] ?? ''),
            'date_of_inactive' => !empty($_POST['date_of_inactive']) ? $_POST['date_of_inactive'] : null
        ];

        // Validation
        if (empty($applicant_data['first_name'])) $errors[] = "First name is required";
        if (empty($applicant_data['last_name'])) $errors[] = "Last name is required";
        if (empty($applicant_data['birth_date'])) $errors[] = "Birth date is required";
        if (empty($applicant_data['gender'])) $errors[] = "Gender is required";
        if (empty($applicant_data['living_arrangement'])) $errors[] = "Living arrangement is required"; // Added

        // If no errors, proceed with updates
        if (empty($errors)) {
            try {
                // Update applicants table
                $stmt = $pdo->prepare("
                    UPDATE applicants SET 
                        first_name = ?, middle_name = ?, last_name = ?, suffix = ?,
                        birth_date = ?, birth_place = ?, gender = ?, civil_status = ?,
                        religion = ?, citizenship = ?, educational_attainment = ?,
                        specialization_skills = ?, community_involvement = ?,
                        problems_needs = ?, remarks = ?, status = ?, validation = ?,
                        living_arrangement = ?, contact_number = ?, date_of_death = ?, 
                        inactive_reason = ?, date_of_inactive = ?, date_modified = NOW()
                    WHERE applicant_id = ?
                ");

                $stmt->execute([
                    $applicant_data['first_name'],
                    $applicant_data['middle_name'],
                    $applicant_data['last_name'],
                    $applicant_data['suffix'],
                    $applicant_data['birth_date'],
                    $applicant_data['birth_place'],
                    $applicant_data['gender'],
                    $applicant_data['civil_status'],
                    $applicant_data['religion'],
                    $applicant_data['citizenship'],
                    $applicant_data['educational_attainment'],
                    $applicant_data['specialization_skills'],
                    $applicant_data['community_involvement'],
                    $applicant_data['problems_needs'],
                    $applicant_data['remarks'],
                    $applicant_data['status'],
                    $applicant_data['validation'],
                    $applicant_data['living_arrangement'],
                    $applicant_data['contact_number'],
                    $applicant_data['date_of_death'],
                    $applicant_data['inactive_reason'],
                    $applicant_data['date_of_inactive'],
                    $applicant_id
                ]);

                // Update or insert address
                $address_data = [
                    'house_no' => trim($_POST['house_no'] ?? ''),
                    'street' => trim($_POST['street'] ?? ''),
                    'barangay' => trim($_POST['barangay'] ?? ''),
                    'municipality' => trim($_POST['municipality'] ?? ''),
                    'province' => trim($_POST['province'] ?? '')
                ];

                $check_address = $pdo->prepare("SELECT COUNT(*) FROM addresses WHERE applicant_id = ?");
                $check_address->execute([$applicant_id]);
                $address_exists = $check_address->fetchColumn();

                if ($address_exists) {
                    $stmt = $pdo->prepare("
                        UPDATE addresses SET 
                            house_no = ?, street = ?, barangay = ?,
                            municipality = ?, province = ?
                        WHERE applicant_id = ?
                    ");
                    $stmt->execute([
                        $address_data['house_no'],
                        $address_data['street'],
                        $address_data['barangay'],
                        $address_data['municipality'],
                        $address_data['province'],
                        $applicant_id
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO addresses 
                        (applicant_id, house_no, street, barangay, municipality, province)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $applicant_id,
                        $address_data['house_no'],
                        $address_data['street'],
                        $address_data['barangay'],
                        $address_data['municipality'],
                        $address_data['province']
                    ]);
                }

                // Update or insert demographic information
                $demographic_data = [
                    'is_ip_member' => isset($_POST['is_ip_member']) ? 1 : 0,
                    'ip_group' => trim($_POST['ip_group'] ?? ''),
                    'tribal_affiliation' => trim($_POST['tribal_affiliation'] ?? ''),
                    'dialect_spoken' => trim($_POST['dialect_spoken'] ?? '')
                ];

                $check_demo = $pdo->prepare("SELECT COUNT(*) FROM applicant_demographics WHERE applicant_id = ?");
                $check_demo->execute([$applicant_id]);
                $demo_exists = $check_demo->fetchColumn();

                if ($demo_exists) {
                    $stmt = $pdo->prepare("
                        UPDATE applicant_demographics SET 
                            is_ip_member = ?, ip_group = ?, tribal_affiliation = ?, dialect_spoken = ?
                        WHERE applicant_id = ?
                    ");
                    $stmt->execute([
                        $demographic_data['is_ip_member'],
                        $demographic_data['ip_group'],
                        $demographic_data['tribal_affiliation'],
                        $demographic_data['dialect_spoken'],
                        $applicant_id
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO applicant_demographics 
                        (applicant_id, is_ip_member, ip_group, tribal_affiliation, dialect_spoken)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $applicant_id,
                        $demographic_data['is_ip_member'],
                        $demographic_data['ip_group'],
                        $demographic_data['tribal_affiliation'],
                        $demographic_data['dialect_spoken']
                    ]);
                }

                // Update or insert educational background
                $year_graduated = !empty($_POST['year_graduated']) ? $_POST['year_graduated'] : null;
                $education_data = [
                    'educational_attainment' => trim($_POST['edu_attainment'] ?? ''),
                    'school_name' => trim($_POST['school_name'] ?? ''),
                    'course_taken' => trim($_POST['course_taken'] ?? ''),
                    'year_graduated' => $year_graduated
                ];

                $check_edu = $pdo->prepare("SELECT COUNT(*) FROM applicant_educational_background WHERE applicant_id = ?");
                $check_edu->execute([$applicant_id]);
                $edu_exists = $check_edu->fetchColumn();

                if ($edu_exists) {
                    $stmt = $pdo->prepare("
                        UPDATE applicant_educational_background SET 
                            educational_attainment = ?, school_name = ?, course_taken = ?, year_graduated = ?
                        WHERE applicant_id = ?
                    ");
                    $stmt->execute([
                        $education_data['educational_attainment'],
                        $education_data['school_name'],
                        $education_data['course_taken'],
                        $education_data['year_graduated'],
                        $applicant_id
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO applicant_educational_background 
                        (applicant_id, educational_attainment, school_name, course_taken, year_graduated)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $applicant_id,
                        $education_data['educational_attainment'],
                        $education_data['school_name'],
                        $education_data['course_taken'],
                        $education_data['year_graduated']
                    ]);
                }

                // Update or insert registration details
                $registration_data = [
                    'id_number' => trim($_POST['id_number'] ?? ''),
                    'local_control_number' => trim($_POST['local_control_number'] ?? ''),
                    'date_of_registration' => !empty($_POST['date_of_registration']) ? $_POST['date_of_registration'] : null,
                    'registration_status' => $_POST['registration_status'] ?? '',
                    'approval_date' => !empty($_POST['approval_date']) ? $_POST['approval_date'] : null,
                    'registration_remarks' => trim($_POST['registration_remarks'] ?? '')
                ];

                $check_reg = $pdo->prepare("SELECT COUNT(*) FROM applicant_registration_details WHERE applicant_id = ?");
                $check_reg->execute([$applicant_id]);
                $reg_exists = $check_reg->fetchColumn();

                if ($reg_exists) {
                    $stmt = $pdo->prepare("
                        UPDATE applicant_registration_details SET 
                            id_number = ?, local_control_number = ?, date_of_registration = ?,
                            registration_status = ?, approval_date = ?, registration_remarks = ?
                        WHERE applicant_id = ?
                    ");
                    $stmt->execute([
                        $registration_data['id_number'],
                        $registration_data['local_control_number'],
                        $registration_data['date_of_registration'],
                        $registration_data['registration_status'],
                        $registration_data['approval_date'],
                        $registration_data['registration_remarks'],
                        $applicant_id
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO applicant_registration_details 
                        (applicant_id, id_number, local_control_number, date_of_registration,
                         registration_status, approval_date, registration_remarks)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $applicant_id,
                        $registration_data['id_number'],
                        $registration_data['local_control_number'],
                        $registration_data['date_of_registration'],
                        $registration_data['registration_status'],
                        $registration_data['approval_date'],
                        $registration_data['registration_remarks']
                    ]);
                }

                // Update or insert economic status
                $economic_data = [
                    'is_pensioner' => isset($_POST['is_pensioner']) ? 1 : 0,
                    'pension_source' => $_POST['pension_source'] ?? '',
                    'pension_source_other' => trim($_POST['pension_source_other'] ?? ''),
                    'pension_amount' => !empty($_POST['pension_amount']) ? $_POST['pension_amount'] : 0,
                    'has_permanent_income' => isset($_POST['has_permanent_income']) ? 1 : 0,
                    'has_family_support' => isset($_POST['has_family_support']) ? 1 : 0,
                    'income_source' => trim($_POST['income_source'] ?? ''),
                    'income_source_detail' => trim($_POST['income_source_detail'] ?? ''),
                    'monthly_income' => !empty($_POST['monthly_income']) ? $_POST['monthly_income'] : 0,
                    'assets_properties' => trim($_POST['assets_properties'] ?? ''),
                    'living_residing_with' => trim($_POST['living_residing_with'] ?? ''),
                    'has_sss' => isset($_POST['has_sss']) ? 1 : 0,
                    'sss_number' => trim($_POST['sss_number'] ?? ''),
                    'has_gsis' => isset($_POST['has_gsis']) ? 1 : 0,
                    'gsis_number' => trim($_POST['gsis_number'] ?? ''),
                    'has_pvao' => isset($_POST['has_pvao']) ? 1 : 0,
                    'pvao_number' => trim($_POST['pvao_number'] ?? ''),
                    'has_insurance' => isset($_POST['has_insurance']) ? 1 : 0,
                    'insurance_number' => trim($_POST['insurance_number'] ?? ''),
                    'has_tin' => isset($_POST['has_tin']) ? 1 : 0,
                    'tin_number' => trim($_POST['tin_number'] ?? ''),
                    'has_philhealth' => isset($_POST['has_philhealth']) ? 1 : 0,
                    'philhealth_number' => trim($_POST['philhealth_number'] ?? ''),
                    'support_type' => trim($_POST['support_type'] ?? ''),
                    'support_cash' => trim($_POST['support_cash'] ?? ''),
                    'support_in_kind' => trim($_POST['support_in_kind'] ?? '')
                ];

                $check_econ = $pdo->prepare("SELECT COUNT(*) FROM economic_status WHERE applicant_id = ?");
                $check_econ->execute([$applicant_id]);
                $econ_exists = $check_econ->fetchColumn();

                if ($econ_exists) {
                    $stmt = $pdo->prepare("
                        UPDATE economic_status SET 
                            is_pensioner = ?, pension_source = ?, pension_source_other = ?, pension_amount = ?,
                            has_permanent_income = ?, has_family_support = ?, income_source = ?, income_source_detail = ?,
                            monthly_income = ?, assets_properties = ?, living_residing_with = ?,
                            has_sss = ?, sss_number = ?, has_gsis = ?, gsis_number = ?, has_pvao = ?, pvao_number = ?, 
                            has_insurance = ?, insurance_number = ?, has_tin = ?, tin_number = ?, has_philhealth = ?, 
                            philhealth_number = ?, support_type = ?, support_cash = ?, support_in_kind = ?
                        WHERE applicant_id = ?
                    ");
                    $stmt->execute([
                        $economic_data['is_pensioner'],
                        $economic_data['pension_source'],
                        $economic_data['pension_source_other'],
                        $economic_data['pension_amount'],
                        $economic_data['has_permanent_income'],
                        $economic_data['has_family_support'],
                        $economic_data['income_source'],
                        $economic_data['income_source_detail'],
                        $economic_data['monthly_income'],
                        $economic_data['assets_properties'],
                        $economic_data['living_residing_with'],
                        $economic_data['has_sss'],
                        $economic_data['sss_number'],
                        $economic_data['has_gsis'],
                        $economic_data['gsis_number'],
                        $economic_data['has_pvao'],
                        $economic_data['pvao_number'],
                        $economic_data['has_insurance'],
                        $economic_data['insurance_number'],
                        $economic_data['has_tin'],
                        $economic_data['tin_number'],
                        $economic_data['has_philhealth'],
                        $economic_data['philhealth_number'],
                        $economic_data['support_type'],
                        $economic_data['support_cash'],
                        $economic_data['support_in_kind'],
                        $applicant_id
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO economic_status 
                        (applicant_id, is_pensioner, pension_source, pension_source_other, pension_amount,
                         has_permanent_income, has_family_support, income_source, income_source_detail,
                         monthly_income, assets_properties, living_residing_with,
                         has_sss, sss_number, has_gsis, gsis_number, has_pvao, pvao_number, 
                         has_insurance, insurance_number, has_tin, tin_number, has_philhealth, 
                         philhealth_number, support_type, support_cash, support_in_kind)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $applicant_id,
                        $economic_data['is_pensioner'],
                        $economic_data['pension_source'],
                        $economic_data['pension_source_other'],
                        $economic_data['pension_amount'],
                        $economic_data['has_permanent_income'],
                        $economic_data['has_family_support'],
                        $economic_data['income_source'],
                        $economic_data['income_source_detail'],
                        $economic_data['monthly_income'],
                        $economic_data['assets_properties'],
                        $economic_data['living_residing_with'],
                        $economic_data['has_sss'],
                        $economic_data['sss_number'],
                        $economic_data['has_gsis'],
                        $economic_data['gsis_number'],
                        $economic_data['has_pvao'],
                        $economic_data['pvao_number'],
                        $economic_data['has_insurance'],
                        $economic_data['insurance_number'],
                        $economic_data['has_tin'],
                        $economic_data['tin_number'],
                        $economic_data['has_philhealth'],
                        $economic_data['philhealth_number'],
                        $economic_data['support_type'],
                        $economic_data['support_cash'],
                        $economic_data['support_in_kind']
                    ]);
                }

                // Update or insert health condition
                $health_data = [
                    'has_existing_illness' => isset($_POST['has_existing_illness']) ? 1 : 0,
                    'illness_details' => trim($_POST['illness_details'] ?? ''),
                    'has_disability' => isset($_POST['has_disability']) ? 1 : 0,
                    'disability_details' => trim($_POST['disability_details'] ?? ''),
                    'hospitalized_last6mos' => isset($_POST['hospitalized_last6mos']) ? 1 : 0
                ];

                $check_health = $pdo->prepare("SELECT COUNT(*) FROM health_condition WHERE applicant_id = ?");
                $check_health->execute([$applicant_id]);
                $health_exists = $check_health->fetchColumn();

                if ($health_exists) {
                    $stmt = $pdo->prepare("
                        UPDATE health_condition SET 
                            has_existing_illness = ?, illness_details = ?, has_disability = ?,
                            disability_details = ?, hospitalized_last6mos = ?
                        WHERE applicant_id = ?
                    ");
                    $stmt->execute([
                        $health_data['has_existing_illness'],
                        $health_data['illness_details'],
                        $health_data['has_disability'],
                        $health_data['disability_details'],
                        $health_data['hospitalized_last6mos'],
                        $applicant_id
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO health_condition 
                        (applicant_id, has_existing_illness, illness_details, has_disability,
                         disability_details, hospitalized_last6mos)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $applicant_id,
                        $health_data['has_existing_illness'],
                        $health_data['illness_details'],
                        $health_data['has_disability'],
                        $health_data['disability_details'],
                        $health_data['hospitalized_last6mos']
                    ]);
                }

                // Handle illnesses
                $illnesses = $_POST['illnesses'] ?? [];
                $illness_dates = $_POST['illness_dates'] ?? [];

                // Delete existing illnesses
                $delete_stmt = $pdo->prepare("DELETE FROM senior_illness WHERE applicant_id = ?");
                $delete_stmt->execute([$applicant_id]);

                // Insert new illnesses
                if (!empty($illnesses)) {
                    $insert_stmt = $pdo->prepare("INSERT INTO senior_illness (applicant_id, illness_name, illness_date) VALUES (?, ?, ?)");
                    for ($i = 0; $i < count($illnesses); $i++) {
                        if (!empty($illnesses[$i]) && !empty($illness_dates[$i])) {
                            $insert_stmt->execute([$applicant_id, $illnesses[$i], $illness_dates[$i]]);
                        }
                    }
                }

                // Commit transaction
                $pdo->commit();

                // Set success message
                $success_message = "Senior information updated successfully!";

                // Force a page refresh to show updated data
                echo '<meta http-equiv="refresh" content="0">';
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = "Error updating data: " . $e->getMessage();
                error_log("Update error: " . $e->getMessage());
            }
        } else {
            $pdo->rollBack();
        }
    }

    // Always fetch fresh data for the form (whether POST or GET)
    $stmt = $pdo->prepare("
        SELECT a.*,
               CONCAT(a.last_name, ', ', a.first_name, ' ', COALESCE(a.middle_name, '')) as full_name
        FROM applicants a 
        WHERE a.applicant_id = ?
    ");
    $stmt->execute([$applicant_id]);
    $senior_data['applicant'] = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$senior_data['applicant']) {
        header("Location: activelist.php");
        exit();
    }

    // Fetch other related data
    $stmt = $pdo->prepare("SELECT * FROM addresses WHERE applicant_id = ?");
    $stmt->execute([$applicant_id]);
    $senior_data['address'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmt = $pdo->prepare("SELECT * FROM applicant_demographics WHERE applicant_id = ?");
    $stmt->execute([$applicant_id]);
    $senior_data['demographic'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmt = $pdo->prepare("SELECT * FROM applicant_educational_background WHERE applicant_id = ?");
    $stmt->execute([$applicant_id]);
    $senior_data['education'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmt = $pdo->prepare("SELECT * FROM applicant_registration_details WHERE applicant_id = ?");
    $stmt->execute([$applicant_id]);
    $senior_data['registration'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmt = $pdo->prepare("SELECT * FROM economic_status WHERE applicant_id = ?");
    $stmt->execute([$applicant_id]);
    $senior_data['economic'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmt = $pdo->prepare("SELECT * FROM health_condition WHERE applicant_id = ?");
    $stmt->execute([$applicant_id]);
    $senior_data['health'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmt = $pdo->prepare("SELECT * FROM senior_illness WHERE applicant_id = ? ORDER BY illness_date DESC");
    $stmt->execute([$applicant_id]);
    $senior_data['illnesses'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Senior Information - <?php echo htmlspecialchars(getArrayValue($senior_data['applicant'], 'full_name', 'Senior')); ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <link rel="apple-touch-icon" href="/MSWDPALUAN_SYSTEM-MAIN/img/paluan.png">
    <style>
        /* Enhanced logo styling for page display */
        .highlighted-logo {
            filter: 
                brightness(1.3)      /* Make brighter */
                contrast(1.2)        /* Increase contrast */
                saturate(1.5)        /* Make colors more vibrant */
                drop-shadow(0 0 8px #3b82f6)  /* Blue glow */
                drop-shadow(0 0 12px rgba(59, 130, 246, 0.7));
            
            /* Optional border */
            border: 3px solid rgba(59, 130, 246, 0.4);
            border-radius: 12px;
            
            /* Inner glow effect */
            box-shadow: 
                inset 0 0 10px rgba(255, 255, 255, 0.6),
                0 0 20px rgba(59, 130, 246, 0.5);
            
            /* Animation for extra attention */
            animation: pulse-glow 2s infinite alternate;
        }
        
        @keyframes pulse-glow {
            from {
                box-shadow: 
                    inset 0 0 10px rgba(255, 255, 255, 0.6),
                    0 0 15px rgba(59, 130, 246, 0.5);
            }
            to {
                box-shadow: 
                    inset 0 0 15px rgba(255, 255, 255, 0.8),
                    0 0 25px rgba(59, 130, 246, 0.8);
            }
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .step {
            display: none;
        }

        .step.active {
            display: block;
        }

        .step-indicator {
            padding: 8px 16px;
            border-radius: 20px;
            background-color: #e5e7eb;
            color: #6b7280;
            font-weight: 500;
        }

        .step-indicator.active {
            background-color: #3b82f6;
            color: white;
        }

        .required:after {
            content: " *";
            color: #ef4444;
        }

        .error {
            border-color: #ef4444 !important;
        }

        .error-message {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* Loading overlay */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading-spinner {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900">
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-4 text-gray-700">Saving changes, please wait...</p>
        </div>
    </div>

    <div class="antialiased">
        <nav class="bg-white border-b border-gray-200 px-4 py-2.5 dark:bg-gray-800 dark:border-gray-700 fixed left-0 right-0 top-0 z-50">
            <div class="flex flex-wrap justify-between items-center">
                <div class="flex justify-start items-center">
                    <button data-drawer-target="drawer-navigation" data-drawer-toggle="drawer-navigation"
                        aria-controls="drawer-navigation"
                        class="p-2 mr-2 text-gray-600 rounded-lg cursor-pointer md:hidden hover:text-gray-900 hover:bg-gray-100 focus:bg-gray-100 dark:focus:bg-gray-700 focus:ring-2 focus:ring-gray-100 dark:focus:ring-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                        <svg aria-hidden="true" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <svg aria-hidden="true" class="hidden w-6 h-6" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span class="sr-only">Toggle sidebar</span>
                    </button>
                    <a href="#" class="flex items-center justify-between mr-4">
                        <img src="/MSWDPALUAN_SYSTEM-MAIN/img/MSWD_LOGO-removebg-preview.png"
                            class="mr-3 h-10 border border-gray-50 rounded-full py-1.5 px-1 bg-gray-50 dark:bg-gray-700 dark:border-gray-600"
                            alt="MSWD LOGO" />
                        <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">MSWD PALUAN</span>
                    </a>
                </div>
                <div class="flex items-center lg:order-2">
                    <button type="button"
                        class="flex mx-3 cursor-pointer text-sm bg-gray-800 rounded-full md:mr-0 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
                        id="user-menu-button" aria-expanded="false" data-dropdown-toggle="dropdown">
                        <span class="sr-only">Open user menu</span>
                        <img class="w-8 h-8 rounded-full object-cover"
                            src="<?php echo htmlspecialchars($profile_photo_url); ?>"
                            alt="user photo" />
                    </button>
                    <div class="hidden z-50 my-4 w-56 text-base list-none bg-white divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600 rounded-xl"
                        id="dropdown">
                        <div class="py-3 px-4">
                            <span class="block text-sm font-semibold text-gray-900 dark:text-white">
                                <?php
                                if (isset($_SESSION['fullname']) && !empty($_SESSION['fullname'])) {
                                    echo htmlspecialchars($_SESSION['fullname']);
                                } else if (isset($_SESSION['firstname']) && isset($_SESSION['lastname'])) {
                                    echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
                                } else {
                                    echo 'User';
                                }
                                ?>
                            </span>
                            <span class="block text-sm text-gray-900 truncate dark:text-white">
                                <?php
                                if (isset($_SESSION['user_type']) && !empty($_SESSION['user_type'])) {
                                    echo htmlspecialchars($_SESSION['user_type']);
                                } else if (isset($_SESSION['role_name']) && !empty($_SESSION['role_name'])) {
                                    echo htmlspecialchars($_SESSION['role_name']);
                                } else {
                                    echo 'User Type';
                                }
                                ?>
                            </span>
                        </div>
                        <ul class="py-1 text-gray-700 dark:text-gray-300" aria-labelledby="dropdown">
                            <li>
                                <a href="/MSWDPALUAN_SYSTEM-MAIN/php/login/logout.php"
                                    class="block py-2 px-4 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Sign out
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Sidebar -->
        <aside
            class="fixed top-0 left-0 z-40 w-64 h-screen pt-14 transition-transform -translate-x-full bg-white border-r border-gray-200 md:translate-x-0 dark:bg-gray-800 dark:border-gray-700"
            aria-label="Sidenav" id="drawer-navigation">
            <div class="overflow-y-auto py-5 px-3 h-full bg-white dark:bg-gray-800">
                <p class="text-lg font-medium text-gray-900 dark:text-white mb-5">User Panel</p>
                <ul class="space-y-2">
                    <li>
                        <a href="../admin_dashboard.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                            <i class="fas fa-tachometer-alt w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="../register.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-user-plus w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Register</span>
                        </a>
                    </li>
                    <li>
                        <button type="button" aria-controls="dropdown-pages" data-collapse-toggle="dropdown-pages"
                            class="flex items-center cursor-pointer p-2 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                            <i class="fas fa-list w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="flex-1 ml-3 text-left whitespace-nowrap">Master List</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <ul id="dropdown-pages" class=" py-2 space-y-2">
                            <li>
                                <a href="#"
                                    class="flex items-center p-2 pl-11 w-full text-base text-blue-700 rounded-lg dark:text-white bg-blue-100 hover:bg-blue-200 dark:bg-blue-700 dark:hover:bg-blue-600 group">
                                    <i class="fas fa-check-circle mr-2 text-sm text-blue-700 dark:text-white group-hover:text-blue-800 dark:group-hover:text-white"></i>Active List
                                </a>
                            </li>
                            <li>
                                <a href="../SeniorList/inactivelist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                    <i class="fas fa-times-circle mr-2 text-sm"></i>Inactive List
                                </a>
                            </li>
                            <li>
                                <a href="../SeniorList/deceasedlist.php?session_context=<?php echo $ctx; ?>"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
                                    <i class="fas fa-cross mr-2 text-sm"></i>Deceased List
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="../benefits.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-gift w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Benefits</span>
                        </a>
                    </li>
                    <li>
                        <a href="../generate_id.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-id-card w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Generate ID</span>
                        </a>
                    </li>
                    <li>
                        <a href="../reports/report.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-chart-bar w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Report</span>
                        </a>
                    </li>
                </ul>
                <ul class="pt-5 mt-5 space-y-2 border-t border-gray-200 dark:border-gray-700">
                    <li>
                        <a href="../archived.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-archive w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Archived</span>
                        </a>
                    </li>
                    <li>
                        <a href="/MSWDPALUAN_SYSTEM-MAIN/html/settings/profile.php?session_context=<?php echo $ctx; ?>"
                            class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group">
                            <i class="fas fa-cog w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"></i>
                            <span class="ml-3">Settings</span>
                        </a>
                    </li>
                </ul>
            </div>
        </aside>
        <main class="p-4 md:ml-64 pt-20">
            <!-- Header with navigation -->
            <div class="w-full flex justify-between items-center mb-6">
                <a href="senior_demographic.php?session_context=<?php echo $ctx; ?>&id=<?php echo $applicant_id; ?>"
                    class="text-white flex flex-row items-center cursor-pointer bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 transition duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Profile
                </a>

                <div class="text-center">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Edit Senior Information
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        <?php echo htmlspecialchars(getArrayValue($senior_data['applicant'], 'full_name')); ?>
                    </p>
                </div>

                <div class="w-24"></div> <!-- Spacer for alignment -->
            </div>

            <!-- Progress Steps -->
            <div class="mb-8">
                <div class="flex justify-center space-x-4 mb-4">
                    <div class="step-indicator active" data-step="1">1. Personal</div>
                    <div class="step-indicator" data-step="2">2. Address</div>
                    <div class="step-indicator" data-step="3">3. Demographics</div>
                    <div class="step-indicator" data-step="4">4. Economic</div>
                    <div class="step-indicator" data-step="5">5. Health</div>
                    <div class="step-indicator" data-step="6">6. Review</div>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                    <div class="bg-blue-600 h-2.5 rounded-full w-1/6" id="progress-bar"></div>
                </div>
            </div>

            <!-- Messages -->
            <?php if (!empty($errors)): ?>
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <!-- Multi-step Form -->
            <form method="POST" action="" id="editForm">
                <!-- Step 1: Personal Information -->
                <div class="step active" id="step-1">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                            <i class="fas fa-user-circle mr-3"></i>Personal Information
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Name Fields -->
                            <div>
                                <label for="first_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white required">First Name</label>
                                <input type="text" id="first_name" name="first_name"
                                    value="<?php echo htmlspecialchars(getArrayValue($senior_data['applicant'], 'first_name')); ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                            </div>

                            <div>
                                <label for="middle_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Middle Name</label>
                                <input type="text" id="middle_name" name="middle_name"
                                    value="<?php echo htmlspecialchars(getArrayValue($senior_data['applicant'], 'middle_name')); ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            </div>

                            <div>
                                <label for="last_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white required">Last Name</label>
                                <input type="text" id="last_name" name="last_name"
                                    value="<?php echo htmlspecialchars(getArrayValue($senior_data['applicant'], 'last_name')); ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                            </div>

                            <div>
                                <label for="suffix" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Suffix</label>
                                <input type="text" id="suffix" name="suffix"
                                    value="<?php echo htmlspecialchars(getArrayValue($senior_data['applicant'], 'suffix')); ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            </div>

                            <!-- Birth Information -->
                            <div>
                                <label for="birth_date" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white required">Birth Date</label>
                                <input type="date" id="birth_date" name="birth_date"
                                    value="<?php echo formatDateForInput(getArrayValue($senior_data['applicant'], 'birth_date')); ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                            </div>

                            <div>
                                <label for="birth_place" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white required">Birth Place</label>
                                <input type="text" id="birth_place" name="birth_place"
                                    value="<?php echo htmlspecialchars(getArrayValue($senior_data['applicant'], 'birth_place')); ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                            </div>

                            <!-- Gender -->
                            <div>
                                <label for="gender" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white required">Gender</label>
                                <select id="gender" name="gender"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                                    <option value="">Select Gender</option>
                                    <?php foreach ($genders as $gender): ?>
                                        <option value="<?php echo $gender; ?>" <?php echo (getArrayValue($senior_data['applicant'], 'gender') == $gender) ? 'selected' : ''; ?>>
                                            <?php echo $gender; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Civil Status -->
                            <div>
                                <label for="civil_status" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white required">Civil Status</label>
                                <select id="civil_status" name="civil_status"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                                    <option value="">Select Civil Status</option>
                                    <?php foreach ($civil_statuses as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo (getArrayValue($senior_data['applicant'], 'civil_status') == $status) ? 'selected' : ''; ?>>
                                            <?php echo $status; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Religion -->
                            <div>
                                <label for="religion" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Religion</label>
                                <input type="text" id="religion" name="religion"
                                    value="<?php echo htmlspecialchars(getArrayValue($senior_data['applicant'], 'religion')); ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            </div>

                            <!-- Citizenship -->
                            <div>
                                <label for="citizenship" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Citizenship</label>
                                <input type="text" id="citizenship" name="citizenship"
                                    value="<?php echo htmlspecialchars(getArrayValue($senior_data['applicant'], 'citizenship')); ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            </div>

                            <!-- Living Arrangement -->
                            <div>
                                <label for="living_arrangement" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white required">Living Arrangement</label>
                                <select id="living_arrangement" name="living_arrangement" required
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                    <option value="">Select Arrangement</option>
                                    <?php
                                    $current_living_arrangement = getArrayValue($senior_data['applicant'], 'living_arrangement');
                                    foreach ($living_arrangements as $arrangement):
                                        $selected = (trim($current_living_arrangement) === trim($arrangement)) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo htmlspecialchars($arrangement); ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($arrangement); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Contact Number -->
                            <div>
                                <label for="contact_number" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Contact Number</label>
                                <input type="tel" id="contact_number" name="contact_number"
                                    value="<?php echo htmlspecialchars(getArrayValue($senior_data['applicant'], 'contact_number')); ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                    placeholder="e.g., 09123456789">
                            </div>
                        </div>

                        <!-- Status and Validation -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div>
                                <label for="status" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Status</label>
                                <select id="status" name="status"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                    <?php foreach ($status_options as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo (getArrayValue($senior_data['applicant'], 'status') == $status) ? 'selected' : ''; ?>>
                                            <?php echo $status; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="validation" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Validation</label>
                                <select id="validation" name="validation"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                    <?php foreach ($validation_options as $validation): ?>
                                        <option value="<?php echo $validation; ?>" <?php echo (getArrayValue($senior_data['applicant'], 'validation') == $validation) ? 'selected' : ''; ?>>
                                            <?php echo $validation; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Conditional fields for status -->
                        <div id="status-fields" class="mt-6 hidden">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div id="deceased-fields" class="hidden">
                                    <label for="date_of_death" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Date of Death</label>
                                    <input type="date" id="date_of_death" name="date_of_death"
                                        value="<?php echo formatDateForInput(getArrayValue($senior_data['applicant'], 'date_of_death')); ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>

                                <div id="inactive-fields" class="hidden">
                                    <label for="date_of_inactive" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white mt-4">Date of Inactive</label>
                                    <input type="date" id="date_of_inactive" name="date_of_inactive"
                                        value="<?php echo formatDateForInput(getArrayValue($senior_data['applicant'], 'date_of_inactive')); ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <div></div> <!-- Empty div for spacing -->
                        <button type="button" onclick="nextStep(2)"
                            class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            Next: Address <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Address Information -->
                <div class="step" id="step-2">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                            <i class="fas fa-home mr-3"></i>Address Information
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="house_no" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">House No./Unit</label>
                                <input type="text" id="house_no" name="house_no"
                                    value="<?php echo htmlspecialchars(getArrayValue($senior_data['address'], 'house_no')); ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            </div>

                            <div>
                                <label for="street" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Street</label>
                                <input type="text" id="street" name="street"
                                    value="<?php echo htmlspecialchars(getArrayValue($senior_data['address'], 'street')); ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            </div>

                            <div>
                                <label for="barangay" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white required">Barangay</label>
                                <input type="text" id="barangay" name="barangay"
                                    value="<?php echo htmlspecialchars(getArrayValue($senior_data['address'], 'barangay')); ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                            </div>

                            <div>
                                <label for="municipality" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white required">Municipality</label>
                                <input type="text" id="municipality" name="municipality"
                                    value="<?php echo htmlspecialchars(getArrayValue($senior_data['address'], 'municipality')); ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                            </div>

                            <div>
                                <label for="province" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white required">Province</label>
                                <input type="text" id="province" name="province"
                                    value="<?php echo htmlspecialchars(getArrayValue($senior_data['address'], 'province')); ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                            </div>

                            <!-- <div>
                                <label for="region" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Region</label>
                                <input type="text" id="region" name="region"
                                    value="<?php echo htmlspecialchars(getArrayValue($senior_data['address'], 'region')); ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            </div> -->
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button" onclick="prevStep(1)"
                            class="text-gray-700 bg-gray-100 hover:bg-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            <i class="fas fa-arrow-left mr-2"></i>Back
                        </button>
                        <button type="button" onclick="nextStep(3)"
                            class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            Next: Demographics <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Demographics & Registration -->
                <div class="step" id="step-3">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                            <i class="fas fa-users mr-3"></i>Demographic Profile & Registration
                        </h2>

                        <!-- IP Information -->
                        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">IP Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex items-center">
                                    <input id="is_ip_member" type="checkbox" name="is_ip_member" value="1"
                                        <?php echo (getArrayValue($senior_data['demographic'], 'is_ip_member') == 1) ? 'checked' : ''; ?>
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="is_ip_member" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                        IP Member
                                    </label>
                                </div>

                                <div>
                                    <label for="ip_group" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">IP Group</label>
                                    <input type="text" id="ip_group" name="ip_group"
                                        value="<?php echo htmlspecialchars(getArrayValue($senior_data['demographic'], 'ip_group')); ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="tribal_affiliation" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tribal Affiliation</label>
                                    <input type="text" id="tribal_affiliation" name="tribal_affiliation"
                                        value="<?php echo htmlspecialchars(getArrayValue($senior_data['demographic'], 'tribal_affiliation')); ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="dialect_spoken" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Dialect Spoken</label>
                                    <input type="text" id="dialect_spoken" name="dialect_spoken"
                                        value="<?php echo htmlspecialchars(getArrayValue($senior_data['demographic'], 'dialect_spoken')); ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Educational Background -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Educational Background</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="edu_attainment" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Educational Attainment</label>
                                    <select id="edu_attainment" name="edu_attainment"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option value="">Select Level</option>
                                        <?php foreach ($educational_levels as $level): ?>
                                            <option value="<?php echo $level; ?>" <?php echo (getArrayValue($senior_data['education'], 'educational_attainment', getArrayValue($senior_data['applicant'], 'educational_attainment')) == $level) ? 'selected' : ''; ?>>
                                                <?php echo $level; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="school_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">School Name</label>
                                    <input type="text" id="school_name" name="school_name"
                                        value="<?php echo htmlspecialchars(getArrayValue($senior_data['education'], 'school_name')); ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="course_taken" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Course Taken</label>
                                    <input type="text" id="course_taken" name="course_taken"
                                        value="<?php echo htmlspecialchars(getArrayValue($senior_data['education'], 'course_taken')); ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="year_graduated" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Year Graduated</label>
                                    <input type="number" id="year_graduated" name="year_graduated" min="1900" max="<?php echo date('Y'); ?>"
                                        value="<?php echo formatYearForInput(getArrayValue($senior_data['education'], 'year_graduated')); ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Registration Details -->
                        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Registration Details</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="id_number" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">OSCA ID Number</label>
                                    <input type="text" id="id_number" name="id_number"
                                        value="<?php echo htmlspecialchars(getArrayValue($senior_data['registration'], 'id_number')); ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="local_control_number" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Local Control Number</label>
                                    <input type="text" id="local_control_number" name="local_control_number"
                                        value="<?php echo htmlspecialchars(getArrayValue($senior_data['registration'], 'local_control_number')); ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="date_of_registration" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Date of Registration</label>
                                    <input type="date" id="date_of_registration" name="date_of_registration"
                                        value="<?php echo formatDateForInput(getArrayValue($senior_data['registration'], 'date_of_registration')); ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="registration_status" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Registration Status</label>
                                    <select id="registration_status" name="registration_status"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option value="">Select Status</option>
                                        <?php foreach ($registration_statuses as $status): ?>
                                            <option value="<?php echo $status; ?>" <?php echo (getArrayValue($senior_data['registration'], 'registration_status') == $status) ? 'selected' : ''; ?>>
                                                <?php echo $status; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="approval_date" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Approval Date</label>
                                    <input type="date" id="approval_date" name="approval_date"
                                        value="<?php echo formatDateForInput(getArrayValue($senior_data['registration'], 'approval_date')); ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="registration_remarks" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Registration Remarks</label>
                                    <textarea id="registration_remarks" name="registration_remarks" rows="3"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"><?php echo htmlspecialchars(getArrayValue($senior_data['registration'], 'registration_remarks')); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Additional Information</h3>
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label for="specialization_skills" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Specialization/Skills</label>
                                    <textarea id="specialization_skills" name="specialization_skills" rows="3"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"><?php echo htmlspecialchars(getArrayValue($senior_data['applicant'], 'specialization_skills')); ?></textarea>
                                </div>

                                <div>
                                    <label for="community_involvement" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Community Involvement</label>
                                    <textarea id="community_involvement" name="community_involvement" rows="3"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"><?php echo htmlspecialchars(getArrayValue($senior_data['applicant'], 'community_involvement')); ?></textarea>
                                </div>

                                <div>
                                    <label for="problems_needs" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Problems/Needs</label>
                                    <textarea id="problems_needs" name="problems_needs" rows="3"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"><?php echo htmlspecialchars(getArrayValue($senior_data['applicant'], 'problems_needs')); ?></textarea>
                                </div>

                                <div>
                                    <label for="remarks" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Remarks</label>
                                    <textarea id="remarks" name="remarks" rows="3"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"><?php echo htmlspecialchars(getArrayValue($senior_data['applicant'], 'remarks')); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button" onclick="prevStep(2)"
                            class="text-gray-700 bg-gray-100 hover:bg-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            <i class="fas fa-arrow-left mr-2"></i>Back
                        </button>
                        <button type="button" onclick="nextStep(4)"
                            class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            Next: Economic Status <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 4: Economic Status -->
                <div class="step" id="step-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                            <i class="fas fa-chart-line mr-3"></i>Economic Status
                        </h2>

                        <!-- Pension Information -->
                        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Pension Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex items-center">
                                    <input id="is_pensioner" type="checkbox" name="is_pensioner" value="1"
                                        <?php echo (getArrayValue($senior_data['economic'], 'is_pensioner') == 1) ? 'checked' : ''; ?>
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="is_pensioner" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                        Is Pensioner?
                                    </label>
                                </div>

                                <div>
                                    <label for="pension_source" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Pension Source</label>
                                    <select id="pension_source" name="pension_source"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option value="">Select Source</option>
                                        <?php foreach ($pension_sources as $source): ?>
                                            <option value="<?php echo $source; ?>" <?php echo (getArrayValue($senior_data['economic'], 'pension_source') == $source) ? 'selected' : ''; ?>>
                                                <?php echo $source; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div id="pension_other_div" class="<?php echo (getArrayValue($senior_data['economic'], 'pension_source') == 'Others') ? '' : 'hidden'; ?>">
                                    <label for="pension_source_other" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Other Pension Source</label>
                                    <input type="text" id="pension_source_other" name="pension_source_other"
                                        value="<?php echo htmlspecialchars(getArrayValue($senior_data['economic'], 'pension_source_other')); ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="pension_amount" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Pension Amount</label>
                                    <input type="number" id="pension_amount" name="pension_amount" step="0.01"
                                        value="<?php echo htmlspecialchars(getArrayValue($senior_data['economic'], 'pension_amount')); ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Income Information -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Income Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex items-center">
                                    <input id="has_permanent_income" type="checkbox" name="has_permanent_income" value="1"
                                        <?php echo (getArrayValue($senior_data['economic'], 'has_permanent_income') == 1) ? 'checked' : ''; ?>
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="has_permanent_income" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                        Has Permanent Income?
                                    </label>
                                </div>

                                <div>
                                    <label for="income_source" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Income Source</label>
                                    <select id="income_source" name="income_source"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option value="">Select Source</option>
                                        <?php foreach ($income_sources as $source): ?>
                                            <option value="<?php echo $source; ?>" <?php echo (getArrayValue($senior_data['economic'], 'income_source') == $source) ? 'selected' : ''; ?>>
                                                <?php echo $source; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="income_source_detail" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Income Source Details</label>
                                    <input type="text" id="income_source_detail" name="income_source_detail"
                                        value="<?php echo htmlspecialchars(getArrayValue($senior_data['economic'], 'income_source_detail')); ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="monthly_income" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Monthly Income (PHP)</label>
                                    <input type="number" id="monthly_income" name="monthly_income" step="0.01"
                                        value="<?php echo htmlspecialchars(getArrayValue($senior_data['economic'], 'monthly_income')); ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Family Support -->
                        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Family Support</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex items-center">
                                    <input id="has_family_support" type="checkbox" name="has_family_support" value="1"
                                        <?php echo (getArrayValue($senior_data['economic'], 'has_family_support') == 1) ? 'checked' : ''; ?>
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="has_family_support" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                        Has Family Support?
                                    </label>
                                </div>

                                <div>
                                    <label for="support_type" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Support Type</label>
                                    <select id="support_type" name="support_type"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option value="">Select Type</option>
                                        <?php foreach ($support_types as $type): ?>
                                            <option value="<?php echo $type; ?>" <?php echo (getArrayValue($senior_data['economic'], 'support_type') == $type) ? 'selected' : ''; ?>>
                                                <?php echo $type; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="support_cash" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Cash Support</label>
                                    <input type="text" id="support_cash" name="support_cash"
                                        value="<?php echo htmlspecialchars(getArrayValue($senior_data['economic'], 'support_cash')); ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="support_in_kind" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">In-kind Support</label>
                                    <input type="text" id="support_in_kind" name="support_in_kind"
                                        value="<?php echo htmlspecialchars(getArrayValue($senior_data['economic'], 'support_in_kind')); ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Other Information -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Other Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="assets_properties" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Assets & Properties</label>
                                    <textarea id="assets_properties" name="assets_properties" rows="3"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"><?php echo htmlspecialchars(getArrayValue($senior_data['economic'], 'assets_properties')); ?></textarea>
                                </div>

                                <div>
                                    <label for="living_residing_with" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Living/Residing With</label>
                                    <input type="text" id="living_residing_with" name="living_residing_with"
                                        value="<?php echo htmlspecialchars(getArrayValue($senior_data['economic'], 'living_residing_with')); ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Government Benefits -->
                        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Government Benefits</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                <!-- SSS -->
                                <div>
                                    <div class="flex items-center mb-2">
                                        <input id="has_sss" type="checkbox" name="has_sss" value="1"
                                            <?php echo (getArrayValue($senior_data['economic'], 'has_sss') == 1) ? 'checked' : ''; ?>
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                        <label for="has_sss" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                            SSS
                                        </label>
                                    </div>
                                    <div id="sss_number_div" class="<?php echo (getArrayValue($senior_data['economic'], 'has_sss') == 1) ? '' : 'hidden'; ?> ml-6">
                                        <label for="sss_number" class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">SSS Number</label>
                                        <input type="text" id="sss_number" name="sss_number"
                                            value="<?php echo htmlspecialchars(getArrayValue($senior_data['economic'], 'sss_number', '')); ?>"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                    </div>
                                </div>

                                <!-- GSIS -->
                                <div>
                                    <div class="flex items-center mb-2">
                                        <input id="has_gsis" type="checkbox" name="has_gsis" value="1"
                                            <?php echo (getArrayValue($senior_data['economic'], 'has_gsis') == 1) ? 'checked' : ''; ?>
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                        <label for="has_gsis" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                            GSIS
                                        </label>
                                    </div>
                                    <div id="gsis_number_div" class="<?php echo (getArrayValue($senior_data['economic'], 'has_gsis') == 1) ? '' : 'hidden'; ?> ml-6">
                                        <label for="gsis_number" class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">GSIS Number</label>
                                        <input type="text" id="gsis_number" name="gsis_number"
                                            value="<?php echo htmlspecialchars(getArrayValue($senior_data['economic'], 'gsis_number', '')); ?>"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                    </div>
                                </div>

                                <!-- PVAO -->
                                <div>
                                    <div class="flex items-center mb-2">
                                        <input id="has_pvao" type="checkbox" name="has_pvao" value="1"
                                            <?php echo (getArrayValue($senior_data['economic'], 'has_pvao') == 1) ? 'checked' : ''; ?>
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                        <label for="has_pvao" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                            PVAO
                                        </label>
                                    </div>
                                    <div id="pvao_number_div" class="<?php echo (getArrayValue($senior_data['economic'], 'has_pvao') == 1) ? '' : 'hidden'; ?> ml-6">
                                        <label for="pvao_number" class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">PVAO Number</label>
                                        <input type="text" id="pvao_number" name="pvao_number"
                                            value="<?php echo htmlspecialchars(getArrayValue($senior_data['economic'], 'pvao_number', '')); ?>"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                    </div>
                                </div>

                                <!-- Insurance -->
                                <div>
                                    <div class="flex items-center mb-2">
                                        <input id="has_insurance" type="checkbox" name="has_insurance" value="1"
                                            <?php echo (getArrayValue($senior_data['economic'], 'has_insurance') == 1) ? 'checked' : ''; ?>
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                        <label for="has_insurance" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                            Insurance
                                        </label>
                                    </div>
                                    <div id="insurance_number_div" class="<?php echo (getArrayValue($senior_data['economic'], 'has_insurance') == 1) ? '' : 'hidden'; ?> ml-6">
                                        <label for="insurance_number" class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Insurance Policy Number</label>
                                        <input type="text" id="insurance_number" name="insurance_number"
                                            value="<?php echo htmlspecialchars(getArrayValue($senior_data['economic'], 'insurance_number', '')); ?>"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                    </div>
                                </div>

                                <!-- TIN -->
                                <div>
                                    <div class="flex items-center mb-2">
                                        <input id="has_tin" type="checkbox" name="has_tin" value="1"
                                            <?php echo (getArrayValue($senior_data['economic'], 'has_tin') == 1) ? 'checked' : ''; ?>
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                        <label for="has_tin" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                            TIN
                                        </label>
                                    </div>
                                    <div id="tin_number_div" class="<?php echo (getArrayValue($senior_data['economic'], 'has_tin') == 1) ? '' : 'hidden'; ?> ml-6">
                                        <label for="tin_number" class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">TIN Number</label>
                                        <input type="text" id="tin_number" name="tin_number"
                                            value="<?php echo htmlspecialchars(getArrayValue($senior_data['economic'], 'tin_number', '')); ?>"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                    </div>
                                </div>

                                <!-- PhilHealth -->
                                <div>
                                    <div class="flex items-center mb-2">
                                        <input id="has_philhealth" type="checkbox" name="has_philhealth" value="1"
                                            <?php echo (getArrayValue($senior_data['economic'], 'has_philhealth') == 1) ? 'checked' : ''; ?>
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                        <label for="has_philhealth" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                            PhilHealth
                                        </label>
                                    </div>
                                    <div id="philhealth_number_div" class="<?php echo (getArrayValue($senior_data['economic'], 'has_philhealth') == 1) ? '' : 'hidden'; ?> ml-6">
                                        <label for="philhealth_number" class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">PhilHealth Number</label>
                                        <input type="text" id="philhealth_number" name="philhealth_number"
                                            value="<?php echo htmlspecialchars(getArrayValue($senior_data['economic'], 'philhealth_number', '')); ?>"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button" onclick="prevStep(3)"
                            class="text-gray-700 bg-gray-100 hover:bg-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            <i class="fas fa-arrow-left mr-2"></i>Back
                        </button>
                        <button type="button" onclick="nextStep(5)"
                            class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            Next: Health Condition <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 5: Health Condition -->
                <div class="step" id="step-5">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                            <i class="fas fa-heartbeat mr-3"></i>Health Condition
                        </h2>

                        <!-- Illness Information -->
                        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Illness Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex items-center">
                                    <input id="has_existing_illness" type="checkbox" name="has_existing_illness" value="1"
                                        <?php echo (getArrayValue($senior_data['health'], 'has_existing_illness') == 1) ? 'checked' : ''; ?>
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="has_existing_illness" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                        Has Existing Illness?
                                    </label>
                                </div>

                                <div>
                                    <label for="illness_details" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Illness Details</label>
                                    <textarea id="illness_details" name="illness_details" rows="3"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"><?php echo htmlspecialchars(getArrayValue($senior_data['health'], 'illness_details')); ?></textarea>
                                </div>
                            </div>

                            <!-- Illness History -->
                            <div class="mt-4" id="illness-history">
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Illness History</label>
                                <div id="illness-entries">
                                    <?php if (!empty($senior_data['illnesses'])): ?>
                                        <?php foreach ($senior_data['illnesses'] as $index => $illness): ?>
                                            <div class="illness-entry flex gap-2 mb-2">
                                                <input type="text" name="illnesses[]" placeholder="Illness name"
                                                    value="<?php echo htmlspecialchars($illness['illness_name']); ?>"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-2/3 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                                <input type="date" name="illness_dates[]"
                                                    value="<?php echo htmlspecialchars($illness['illness_date']); ?>"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-1/3 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                                <button type="button" onclick="removeIllnessEntry(this)"
                                                    class="text-red-600 hover:text-red-800">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="illness-entry flex gap-2 mb-2">
                                            <input type="text" name="illnesses[]" placeholder="Illness name"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-2/3 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <input type="date" name="illness_dates[]"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-1/3 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <button type="button" onclick="removeIllnessEntry(this)"
                                                class="text-red-600 hover:text-red-800">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button type="button" onclick="addIllnessEntry()"
                                    class="mt-2 text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-plus mr-1"></i>Add Illness
                                </button>
                            </div>
                        </div>

                        <!-- Disability Information -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Disability Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex items-center">
                                    <input id="has_disability" type="checkbox" name="has_disability" value="1"
                                        <?php echo (getArrayValue($senior_data['health'], 'has_disability') == 1) ? 'checked' : ''; ?>
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="has_disability" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                        Has Disability?
                                    </label>
                                </div>

                                <div>
                                    <label for="disability_details" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Disability Details</label>
                                    <textarea id="disability_details" name="disability_details" rows="3"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"><?php echo htmlspecialchars(getArrayValue($senior_data['health'], 'disability_details')); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Hospitalization -->
                        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Hospitalization</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex items-center">
                                    <input id="hospitalized_last6mos" type="checkbox" name="hospitalized_last6mos" value="1"
                                        <?php echo (getArrayValue($senior_data['health'], 'hospitalized_last6mos') == 1) ? 'checked' : ''; ?>
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="hospitalized_last6mos" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                        Hospitalized in last 6 months?
                                    </label>
                                </div>

                                <!-- <div>
                                    <label for="hospitalization_details" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Hospitalization Details</label>
                                    <textarea id="hospitalization_details" name="hospitalization_details" rows="3"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"><?php echo htmlspecialchars(getArrayValue($senior_data['health'], 'hospitalization_details')); ?></textarea>
                                </div> -->
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button" onclick="prevStep(4)"
                            class="text-gray-700 bg-gray-100 hover:bg-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            <i class="fas fa-arrow-left mr-2"></i>Back
                        </button>
                        <button type="button" onclick="nextStep(6)"
                            class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            Next: Review <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 6: Review & Submit -->
                <div class="step" id="step-6">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                            <i class="fas fa-clipboard-check mr-3"></i>Review Information
                        </h2>

                        <div class="space-y-6">
                            <!-- Personal Info Summary -->
                            <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Personal Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Full Name</p>
                                        <p id="review-fullname" class="font-medium"></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Birth Date</p>
                                        <p id="review-birthdate" class="font-medium"></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Gender</p>
                                        <p id="review-gender" class="font-medium"></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Civil Status</p>
                                        <p id="review-civilstatus" class="font-medium"></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Contact Number</p>
                                        <p id="review-contact" class="font-medium"></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Status</p>
                                        <p id="review-status" class="font-medium"></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Validation</p>
                                        <p id="review-validation" class="font-medium"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Address Summary -->
                            <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Address Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Complete Address</p>
                                        <p id="review-address" class="font-medium"></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Barangay</p>
                                        <p id="review-barangay" class="font-medium"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Economic Summary -->
                            <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Economic Status</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Monthly Income</p>
                                        <p id="review-income" class="font-medium"></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Pensioner</p>
                                        <p id="review-pensioner" class="font-medium"></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Income Source</p>
                                        <p id="review-incomesource" class="font-medium"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Health Summary -->
                            <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Health Condition</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Existing Illness</p>
                                        <p id="review-illness" class="font-medium"></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Disability</p>
                                        <p id="review-disability" class="font-medium"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Terms Confirmation -->
                            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                                <div class="flex items-start">
                                    <input id="terms" type="checkbox" name="terms" value="1" required
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 mt-1">
                                    <label for="terms" class="ms-2 text-sm text-gray-900 dark:text-gray-300">
                                        I confirm that all information provided is accurate to the best of my knowledge.
                                        I understand that false information may result in disqualification from benefits.
                                    </label>
                                </div>
                            </div>

                            <!-- Last Modified Info -->
                            <div class="text-center text-gray-500 dark:text-gray-400 text-sm">
                                <p>This update will be recorded with today's date and time.</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button" onclick="prevStep(5)"
                            class="text-gray-700 bg-gray-100 hover:bg-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            <i class="fas fa-arrow-left mr-2"></i>Back
                        </button>
                        <button type="submit"
                            class="text-white bg-green-600 hover:bg-green-700 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            <i class="fas fa-save mr-2"></i>Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="../../js/tailwind.config.js"></script>
    <script>
        // ---------- THEME INITIALIZATION (MUST BE OUTSIDE DOMContentLoaded) ----------
        // Initialize theme from localStorage or system preference
        function initTheme() {
            const savedTheme = localStorage.getItem('theme');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            let theme = 'light';
            if (savedTheme) {
                theme = savedTheme;
            } else if (systemPrefersDark) {
                theme = 'dark';
            }

            setTheme(theme);
        }

        // Function to set theme
        function setTheme(theme) {
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }
        }

        // Listen for theme changes from other pages
        window.addEventListener('storage', function(e) {
            if (e.key === 'theme') {
                const theme = e.newValue;
                setTheme(theme);
            }
        });

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            if (!localStorage.getItem('theme')) {
                setTheme(e.matches ? 'dark' : 'light');
            }
        });

        // Initialize theme on page load (BEFORE DOMContentLoaded)
        initTheme();
    </script>
    <script>
        let currentStep = 1;
        const totalSteps = 6;

        // Show/hide loading overlay
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }

        function showStep(step) {
            // Hide all steps
            document.querySelectorAll('.step').forEach(el => {
                el.classList.remove('active');
            });

            // Show current step
            const stepElement = document.getElementById(`step-${step}`);
            if (stepElement) {
                stepElement.classList.add('active');
            }

            // Update progress indicators
            document.querySelectorAll('.step-indicator').forEach((el, index) => {
                if (index + 1 <= step) {
                    el.classList.add('active');
                } else {
                    el.classList.remove('active');
                }
            });

            // Update progress bar
            const progress = (step / totalSteps) * 100;
            const progressBar = document.getElementById('progress-bar');
            if (progressBar) {
                progressBar.style.width = `${progress}%`;
            }

            currentStep = step;

            // If on review step, update review data
            if (step === 6) {
                updateReviewData();
            }
        }

        function nextStep(step) {
            // Validate current step before proceeding
            if (validateStep(currentStep)) {
                showStep(step);
            }
        }

        function prevStep(step) {
            showStep(step);
        }

        function validateStep(step) {
            let isValid = true;
            const stepElement = document.getElementById(`step-${step}`);

            if (!stepElement) return true;

            // Get all required inputs in current step
            const requiredInputs = stepElement.querySelectorAll('[required]');

            // Clear previous errors
            stepElement.querySelectorAll('.error-message').forEach(el => el.remove());
            stepElement.querySelectorAll('.error').forEach(el => el.classList.remove('error'));

            requiredInputs.forEach(input => {
                // Skip validation for hidden fields
                if (input.type === 'hidden' || input.closest('.hidden')) {
                    return;
                }

                if (input.type === 'checkbox') {
                    // For checkboxes, check if required but not checked
                    if (input.required && !input.checked) {
                        isValid = false;
                        input.classList.add('error');

                        const error = document.createElement('p');
                        error.className = 'error-message';
                        error.textContent = 'This field is required';
                        input.parentNode.appendChild(error);
                    }
                } else if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('error');

                    // Add error message
                    const error = document.createElement('p');
                    error.className = 'error-message';
                    error.textContent = 'This field is required';
                    input.parentNode.appendChild(error);
                }
            });

            if (!isValid) {
                // Scroll to first error
                const firstError = stepElement.querySelector('.error');
                if (firstError) {
                    firstError.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }

                // Show alert only for step 1-5, not for review step
                if (step < 6) {
                    alert('Please fill in all required fields marked with *');
                }
            }

            return isValid;
        }

        function updateReviewData() {
            // Personal Information
            const firstName = document.getElementById('first_name').value;
            const middleName = document.getElementById('middle_name').value;
            const lastName = document.getElementById('last_name').value;
            const suffix = document.getElementById('suffix').value;
            const contactNumber = document.getElementById('contact_number').value;

            let fullName = `${lastName}${suffix ? ' ' + suffix : ''}, ${firstName}`;
            if (middleName) fullName += ` ${middleName.charAt(0)}.`;

            document.getElementById('review-fullname').textContent = fullName;
            document.getElementById('review-birthdate').textContent = document.getElementById('birth_date').value;
            document.getElementById('review-gender').textContent = document.getElementById('gender').value;
            document.getElementById('review-civilstatus').textContent = document.getElementById('civil_status').value;
            document.getElementById('review-contact').textContent = contactNumber || 'Not specified';
            document.getElementById('review-status').textContent = document.getElementById('status').value;
            document.getElementById('review-validation').textContent = document.getElementById('validation').value;

            // Address Information
            const houseNo = document.getElementById('house_no').value;
            const street = document.getElementById('street').value;
            const barangay = document.getElementById('barangay').value;
            const municipality = document.getElementById('municipality').value;
            const province = document.getElementById('province').value;

            let address = '';
            if (houseNo) address += houseNo + ' ';
            if (street) address += street + ', ';
            address += barangay + ', ' + municipality + ', ' + province;

            document.getElementById('review-address').textContent = address;
            document.getElementById('review-barangay').textContent = barangay;

            // Economic Status
            const monthlyIncome = document.getElementById('monthly_income').value;
            const isPensioner = document.getElementById('is_pensioner').checked ? 'Yes' : 'No';
            const incomeSource = document.getElementById('income_source').value;

            document.getElementById('review-income').textContent = monthlyIncome ? '₱' + parseFloat(monthlyIncome).toLocaleString('en-US', {
                minimumFractionDigits: 2
            }) : 'Not specified';
            document.getElementById('review-pensioner').textContent = isPensioner;
            document.getElementById('review-incomesource').textContent = incomeSource || 'Not specified';

            // Health Condition
            const hasIllness = document.getElementById('has_existing_illness').checked ? 'Yes' : 'No';
            const hasDisability = document.getElementById('has_disability').checked ? 'Yes' : 'No';

            document.getElementById('review-illness').textContent = hasIllness;
            document.getElementById('review-disability').textContent = hasDisability;
        }

        // Status change handler
        const statusElement = document.getElementById('status');
        if (statusElement) {
            statusElement.addEventListener('change', function() {
                const status = this.value;
                const statusFields = document.getElementById('status-fields');
                const deceasedFields = document.getElementById('deceased-fields');
                const inactiveFields = document.getElementById('inactive-fields');

                if (status === 'Deceased') {
                    if (statusFields) statusFields.classList.remove('hidden');
                    if (deceasedFields) deceasedFields.classList.remove('hidden');
                    if (inactiveFields) inactiveFields.classList.add('hidden');
                } else if (status === 'Inactive') {
                    if (statusFields) statusFields.classList.remove('hidden');
                    if (deceasedFields) deceasedFields.classList.add('hidden');
                    if (inactiveFields) inactiveFields.classList.remove('hidden');
                } else {
                    if (statusFields) statusFields.classList.add('hidden');
                    if (deceasedFields) deceasedFields.classList.add('hidden');
                    if (inactiveFields) inactiveFields.classList.add('hidden');
                }
            });
        }

        // Setup government benefits handlers
        function setupGovernmentBenefitsHandlers() {
            const benefits = [{
                    checkbox: 'has_sss',
                    div: 'sss_number_div'
                },
                {
                    checkbox: 'has_gsis',
                    div: 'gsis_number_div'
                },
                {
                    checkbox: 'has_pvao',
                    div: 'pvao_number_div'
                },
                {
                    checkbox: 'has_insurance',
                    div: 'insurance_number_div'
                },
                {
                    checkbox: 'has_tin',
                    div: 'tin_number_div'
                },
                {
                    checkbox: 'has_philhealth',
                    div: 'philhealth_number_div'
                }
            ];

            benefits.forEach(benefit => {
                const checkbox = document.getElementById(benefit.checkbox);
                const div = document.getElementById(benefit.div);

                if (checkbox && div) {
                    // Set initial state based on checkbox
                    if (checkbox.checked) {
                        div.classList.remove('hidden');
                    } else {
                        div.classList.add('hidden');
                    }

                    // Add event listener for future changes
                    checkbox.addEventListener('change', function() {
                        if (this.checked) {
                            div.classList.remove('hidden');
                        } else {
                            div.classList.add('hidden');
                        }
                    });
                }
            });
        }

        // Form submission handler with proper feedback
        const editForm = document.getElementById('editForm');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                // Prevent default to show our custom loading
                e.preventDefault();

                // Validate all steps
                let allValid = true;
                for (let i = 1; i <= 5; i++) {
                    if (!validateStep(i)) {
                        allValid = false;
                        showStep(i);
                        break;
                    }
                }

                if (!allValid) {
                    alert('Please fix errors in the form before submitting.');
                    return false;
                }

                // Validate terms agreement
                const terms = document.getElementById('terms');
                if (terms && !terms.checked) {
                    alert('Please confirm the terms before submitting.');
                    terms.focus();
                    return false;
                }

                // Show loading overlay
                showLoading();

                // Disable submit button
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
                }

                // Submit the form after a short delay to show the loading state
                setTimeout(() => {
                    this.submit();
                }, 500);

                return true;
            });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Show first step
            showStep(1);

            // Trigger status change to show/hide appropriate fields
            if (statusElement) {
                statusElement.dispatchEvent(new Event('change'));
            }

            // Setup government benefits handlers
            setupGovernmentBenefitsHandlers();

            // Clean up invalid date values
            const dateFields = document.querySelectorAll('input[type="date"]');
            dateFields.forEach(field => {
                if (field.value === '0000-00-00' || field.value === '') {
                    field.value = '';
                }
            });

            // Clean up year_graduated field
            const yearField = document.getElementById('year_graduated');
            if (yearField && (yearField.value === '0' || yearField.value === '0000')) {
                yearField.value = '';
            }

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-tooltip-target]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>

</html>