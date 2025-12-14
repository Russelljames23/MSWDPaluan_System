<?php
require_once "../php/login/admin_header.php";
require_once "../php/db_connect.php";

// Fetch active seniors with registration details
$query = "SELECT 
            a.applicant_id,
            CONCAT(a.last_name, ', ', a.first_name, 
                   IF(a.middle_name IS NOT NULL AND a.middle_name != '', 
                      CONCAT(' ', a.middle_name), '')) as full_name,
            a.birth_date,
            TIMESTAMPDIFF(YEAR, a.birth_date, CURDATE()) as age,
            a.gender,
            a.civil_status,
            ad.barangay,
            ad.municipality,
            ad.province,
            a.validation,
            a.status,
            ard.id_number,
            ard.date_of_registration,
            ard.local_control_number
          FROM applicants a
          LEFT JOIN addresses ad ON a.applicant_id = ad.applicant_id
          LEFT JOIN applicant_registration_details ard ON a.applicant_id = ard.applicant_id
          WHERE a.status = 'Active'
          ORDER BY a.last_name, a.first_name";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$seniors = $result->fetch_all(MYSQLI_ASSOC);

// Fetch unique barangays for filter
$barangay_query = "SELECT DISTINCT barangay FROM addresses WHERE barangay IS NOT NULL AND barangay != '' ORDER BY barangay";
$barangay_stmt = $conn->prepare($barangay_query);
$barangay_stmt->execute();
$barangay_result = $barangay_stmt->get_result();
$barangays = $barangay_result->fetch_all(MYSQLI_ASSOC);

$ctx = urlencode($_GET['session_context'] ?? session_id());
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Senior Citizen ID - MSWD Paluan</title>
    <link rel="stylesheet" href="../css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .print-page, .print-page * {
                visibility: visible;
            }
            .print-page {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .page-break {
                page-break-after: always;
            }
        }
        
        /* Custom styles for ID cards */
        .id-card {
            border: 1px solid #000;
            border-radius: 5px;
            padding: 4px;
            font-family: Arial, sans-serif;
            background: white;
        }
        
        .id-header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .id-photo {
            width: 60px;
            height: 60px;
            border: 1px solid #000;
            background: #f0f0f0;
            margin: 0 auto 3px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body>
    <div class="antialiased bg-gray-50 dark:bg-gray-900">
        <!-- Navigation and Sidebar code remains exactly the same -->
        <!-- Copy the entire navigation and sidebar section from your original file here -->
        <!-- I'm omitting it for brevity but make sure to include it -->
        
        <main class="p-4 md:ml-64 h-auto pt-20">
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <!-- Page Header -->
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Generate Senior Citizen ID</h2>
                    <p class="text-gray-600 dark:text-gray-400">Create and print ID cards in batch format (9 per page)</p>
                </div>

                <!-- Search and Filter Section -->
                <div class="mb-6 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <label for="search-senior" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Search Senior Citizen
                            </label>
                            <div class="relative">
                                <input type="text" id="search-senior"
                                    class="w-full p-2.5 pl-10 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                                    placeholder="Search by name, ID number, or barangay">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="md:w-48">
                            <label for="filter-barangay" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Barangay
                            </label>
                            <select id="filter-barangay"
                                class="w-full p-2.5 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                                <option value="all">All Barangays</option>
                                <?php foreach($barangays as $barangay): ?>
                                    <option value="<?php echo htmlspecialchars($barangay['barangay']); ?>">
                                        <?php echo htmlspecialchars($barangay['barangay']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="md:w-48">
                            <label for="filter-validation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Validation Status
                            </label>
                            <select id="filter-validation"
                                class="w-full p-2.5 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                                <option value="all">All Status</option>
                                <option value="Validated">Validated</option>
                                <option value="For Validation">For Validation</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Senior Selection Table -->
                <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4 border-b dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Select Seniors for ID Generation</h3>
                        <div class="w-full md:w-auto flex items-center space-x-3">
                            <button id="select-all-btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600">
                                Select All
                            </button>
                            <button id="deselect-all-btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600">
                                Deselect All
                            </button>
                            <span id="selected-count" class="text-sm text-gray-600 dark:text-gray-400">0 selected</span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3 w-12">
                                        <input id="master-checkbox" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    </th>
                                    <th scope="col" class="px-4 py-3">No.</th>
                                    <th scope="col" class="px-4 py-3">Name</th>
                                    <th scope="col" class="px-4 py-3">Birthdate</th>
                                    <th scope="col" class="px-4 py-3">Age</th>
                                    <th scope="col" class="px-4 py-3">Gender</th>
                                    <th scope="col" class="px-4 py-3">Barangay</th>
                                    <th scope="col" class="px-4 py-3">ID Number</th>
                                    <th scope="col" class="px-4 py-3">Date Issued</th>
                                    <th scope="col" class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody id="seniors-table-body">
                                <?php if(empty($seniors)): ?>
                                    <tr>
                                        <td colspan="10" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                            No active senior citizens found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($seniors as $index => $senior): ?>
                                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 senior-row"
                                            data-barangay="<?php echo htmlspecialchars($senior['barangay'] ?? ''); ?>"
                                            data-validation="<?php echo htmlspecialchars($senior['validation'] ?? ''); ?>">
                                            <td class="px-4 py-3">
                                                <input type="checkbox" class="senior-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                                    data-id="<?php echo htmlspecialchars($senior['applicant_id']); ?>"
                                                    data-name="<?php echo htmlspecialchars($senior['full_name']); ?>"
                                                    data-birthdate="<?php echo htmlspecialchars($senior['birth_date'] ?? ''); ?>"
                                                    data-age="<?php echo htmlspecialchars($senior['age'] ?? ''); ?>"
                                                    data-gender="<?php echo htmlspecialchars($senior['gender'] ?? ''); ?>"
                                                    data-barangay="<?php echo htmlspecialchars($senior['barangay'] ?? ''); ?>"
                                                    data-id-number="<?php echo htmlspecialchars($senior['id_number'] ?? 'N/A'); ?>"
                                                    data-date-issued="<?php echo htmlspecialchars($senior['date_of_registration'] ?? ''); ?>"
                                                    data-local-control="<?php echo htmlspecialchars($senior['local_control_number'] ?? ''); ?>">
                                            </td>
                                            <td class="px-4 py-3"><?php echo $index + 1; ?></td>
                                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                                <?php echo htmlspecialchars($senior['full_name']); ?>
                                            </td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($senior['birth_date'] ?? 'N/A'); ?></td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($senior['age'] ?? 'N/A'); ?></td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($senior['gender'] ?? ''); ?></td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($senior['barangay'] ?? 'N/A'); ?></td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($senior['id_number'] ?? 'N/A'); ?></td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($senior['date_of_registration'] ?? 'N/A'); ?></td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-1 text-xs rounded <?php echo $senior['validation'] === 'Validated' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                    <?php echo htmlspecialchars($senior['validation'] ?? 'Unknown'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ID Preview and Generation Controls -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Preview Controls -->
                    <div class="lg:col-span-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ID Preview & Generation</h3>

                        <!-- Current Selection -->
                        <div class="mb-6">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-2">Selected for Generation</h4>
                            <div id="selected-list" class="max-h-40 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded p-3">
                                <p class="text-sm text-gray-500 dark:text-gray-400 text-center">No seniors selected yet</p>
                            </div>
                        </div>

                        <!-- Generation Options -->
                        <div class="space-y-4">
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                    Signatory Selection
                                </label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="osca-head" class="block text-xs text-gray-700 dark:text-gray-300 mb-1">
                                            OSCA HEAD
                                        </label>
                                        <select id="osca-head" class="w-full p-2 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <option value="EVELYN V. BELTRAN">EVELYN V. BELTRAN</option>
                                            <option value="ROSALINA V. BARRALES">ROSALINA V. BARRALES</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="municipal-mayor" class="block text-xs text-gray-700 dark:text-gray-300 mb-1">
                                            Municipal Mayor
                                        </label>
                                        <select id="municipal-mayor" class="w-full p-2 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <option value="MICHAEL D. DIAZ">MICHAEL D. DIAZ</option>
                                            <option value="MERIAM E. LEYCANO-QUIJANO">MERIAM E. LEYCANO-QUIJANO</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="date-issued" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                    Date Issued (for new IDs)
                                </label>
                                <input type="date" id="date-issued"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                    value="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="pt-4 border-t dark:border-gray-700">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    <p>• IDs will be printed in Long Bond Paper (8.5" x 13") landscape</p>
                                    <p>• 9 IDs per page (Front: ID Info, Back: Benefits)</p>
                                    <p>• IDs will be generated in the exact format of the reference document</p>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <button id="preview-ids-btn"
                                        class="px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white font-medium rounded-lg text-sm focus:ring-4 focus:ring-blue-300 focus:outline-none inline-flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                                        </svg>
                                        Preview IDs
                                    </button>
                                    <button id="generate-pdf-btn"
                                        class="px-5 py-2.5 bg-green-700 hover:bg-green-800 text-white font-medium rounded-lg text-sm focus:ring-4 focus:ring-green-300 focus:outline-none inline-flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V8z" clip-rule="evenodd" />
                                        </svg>
                                        Generate PDF
                                    </button>
                                    <button id="print-ids-btn"
                                        class="px-5 py-2.5 bg-purple-700 hover:bg-purple-800 text-white font-medium rounded-lg text-sm focus:ring-4 focus:ring-purple-300 focus:outline-none inline-flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                                        </svg>
                                        Print IDs
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Preview -->
                    <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-600 rounded-lg p-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 text-center">ID Format Preview</h3>

                        <div class="border-2 border-blue-800 rounded-lg p-3 bg-gradient-to-br from-blue-50 to-gray-50 dark:from-gray-800 dark:to-gray-900">
                            <!-- Republic of the Philippines Header -->
                            <div class="text-center mb-2">
                                <h4 class="text-xs font-bold text-gray-900 dark:text-white">Republic of the Philippines</h4>
                                <h4 class="text-xs font-bold text-gray-900 dark:text-white">Office for Senior Citizens Affairs (OSCA)</h4>
                                <h4 class="text-xs font-bold text-gray-900 dark:text-white">Paluan, Occidental Mindoro</h4>
                            </div>

                            <!-- ID Info -->
                            <div class="flex mb-2">
                                <!-- Photo Area -->
                                <div class="w-1/3 flex flex-col items-center">
                                    <div class="w-16 h-16 border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center">
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400">1x1</span>
                                    </div>
                                    <div class="text-center mt-1">
                                        <div class="text-[8px] font-medium text-gray-700 dark:text-gray-300">ID PIC</div>
                                    </div>
                                </div>

                                <!-- Info Area -->
                                <div class="w-2/3 pl-2">
                                    <div class="space-y-1">
                                        <div>
                                            <label class="text-[8px] font-semibold text-gray-700 dark:text-gray-300">Name:</label>
                                            <div class="text-[9px] font-bold text-gray-900 dark:text-white truncate">SAMPLE SENIOR</div>
                                        </div>
                                        <div>
                                            <label class="text-[8px] font-semibold text-gray-700 dark:text-gray-300">Address:</label>
                                            <div class="text-[9px] text-gray-900 dark:text-white truncate">Brgy. SAMPLE</div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-1">
                                            <div>
                                                <label class="text-[7px] text-gray-700 dark:text-gray-300">Date of Birth</label>
                                                <div class="text-[8px] text-gray-900 dark:text-white">01/01/1940</div>
                                            </div>
                                            <div class="text-center">
                                                <label class="text-[7px] text-gray-700 dark:text-gray-300">Sex</label>
                                                <div class="text-[8px] text-gray-900 dark:text-white">M/F</div>
                                            </div>
                                            <div>
                                                <label class="text-[7px] text-gray-700 dark:text-gray-300">Date Issued</label>
                                                <div class="text-[8px] text-gray-900 dark:text-white"><?php echo date('m/d/Y'); ?></div>
                                            </div>
                                        </div>
                                        <div class="text-center pt-1">
                                            <div class="h-4 border-b border-gray-300 dark:border-gray-600"></div>
                                            <div class="text-[7px] text-gray-700 dark:text-gray-300">Signature/Thumbmark</div>
                                        </div>
                                        <div class="text-center pt-1">
                                            <div class="text-[8px] font-medium text-gray-900 dark:text-white">ID No. <span class="font-bold">000000</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Non-Transferable Notice -->
                            <div class="text-center mt-2">
                                <div class="text-[8px] font-bold text-red-600 dark:text-red-400">THIS CARD IS NON-TRANSFERABLE</div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <div class="text-xs text-gray-600 dark:text-gray-400">Back side contains:</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Benefits and Privileges under RA 9994</div>
                        </div>

                        <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Printing Info:</h4>
                            <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                <li>• Long Bond Paper (8.5" x 13")</li>
                                <li>• Landscape Orientation</li>
                                <li>• 9 IDs per page front & back</li>
                                <li>• Exact document format</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Print Preview Modal -->
            <div id="print-preview-modal" class="hidden fixed inset-0 z-50 overflow-auto bg-black bg-opacity-50">
                <div class="relative w-full max-w-6xl mx-auto my-8">
                    <div class="bg-white rounded-lg shadow-lg">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between p-4 border-b">
                            <h3 class="text-xl font-bold text-gray-900">ID Cards Print Preview</h3>
                            <button id="close-preview-btn" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Preview Content -->
                        <div id="preview-content" class="p-4">
                            <!-- Preview will be generated here -->
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex justify-between items-center p-4 border-t">
                            <div class="text-sm text-gray-600">
                                Showing: <span id="preview-count">0</span> IDs
                            </div>
                            <div class="flex space-x-2">
                                <button id="print-preview-btn" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                                    Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script src="../js/tailwind.config.js"></script>

    <script>
        // Global variables
        let selectedSeniors = new Map();
        let currentPreviewPage = 1;
        let totalPreviewPages = 1;

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Event listeners for buttons
            document.getElementById('select-all-btn').addEventListener('click', selectAll);
            document.getElementById('deselect-all-btn').addEventListener('click', deselectAll);
            document.getElementById('master-checkbox').addEventListener('change', toggleMasterCheckbox);
            document.getElementById('preview-ids-btn').addEventListener('click', previewIDs);
            document.getElementById('generate-pdf-btn').addEventListener('click', generatePDF);
            document.getElementById('print-ids-btn').addEventListener('click', printIDs);
            document.getElementById('close-preview-btn').addEventListener('click', closePreview);
            document.getElementById('print-preview-btn').addEventListener('click', printPreview);

            // Search and filter event listeners
            document.getElementById('search-senior').addEventListener('input', filterTable);
            document.getElementById('filter-barangay').addEventListener('change', filterTable);
            document.getElementById('filter-validation').addEventListener('change', filterTable);

            // Add event listeners to checkboxes
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('senior-checkbox')) {
                    const checkbox = e.target;
                    const id = checkbox.dataset.id;
                    const name = checkbox.dataset.name;

                    if (checkbox.checked) {
                        selectedSeniors.set(id, {
                            name: name,
                            birthdate: checkbox.dataset.birthdate,
                            age: checkbox.dataset.age,
                            gender: checkbox.dataset.gender,
                            barangay: checkbox.dataset.barangay,
                            idNumber: checkbox.dataset.idNumber,
                            dateIssued: checkbox.dataset.dateIssued,
                            localControl: checkbox.dataset.localControl
                        });
                    } else {
                        selectedSeniors.delete(id);
                    }

                    updateSelectedList();
                    updateMasterCheckbox();
                }
            });

            // Update selected list initially
            updateSelectedList();
        });

        // Filter table based on search and filters
        function filterTable() {
            const search = document.getElementById('search-senior').value.toLowerCase();
            const barangay = document.getElementById('filter-barangay').value;
            const validation = document.getElementById('filter-validation').value;

            const rows = document.querySelectorAll('.senior-row');
            
            rows.forEach(row => {
                const name = row.querySelector('.senior-checkbox').dataset.name.toLowerCase();
                const rowBarangay = row.dataset.barangay;
                const rowValidation = row.dataset.validation;
                
                let show = true;
                
                // Apply search filter
                if (search && !name.includes(search)) {
                    show = false;
                }
                
                // Apply barangay filter
                if (barangay !== 'all' && rowBarangay !== barangay) {
                    show = false;
                }
                
                // Apply validation filter
                if (validation !== 'all' && rowValidation !== validation) {
                    show = false;
                }
                
                row.style.display = show ? '' : 'none';
            });
        }

        // Update selected list display
        function updateSelectedList() {
            const listContainer = document.getElementById('selected-list');
            const countElement = document.getElementById('selected-count');

            countElement.textContent = `${selectedSeniors.size} selected`;

            if (selectedSeniors.size === 0) {
                listContainer.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400 text-center">No seniors selected yet</p>';
                return;
            }

            let html = '<div class="space-y-1 max-h-32 overflow-y-auto">';
            selectedSeniors.forEach((senior, id) => {
                html += `
            <div class="flex justify-between items-center text-sm p-1 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                <span class="truncate">${senior.name}</span>
                <button class="text-red-500 hover:text-red-700 ml-2" onclick="removeSelected('${id}')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;
            });
            html += '</div>';
            listContainer.innerHTML = html;
        }

        // Remove selected senior
        function removeSelected(id) {
            selectedSeniors.delete(id);

            // Uncheck in table
            const checkbox = document.querySelector(`.senior-checkbox[data-id="${id}"]`);
            if (checkbox) checkbox.checked = false;

            updateSelectedList();
            updateMasterCheckbox();
        }

        // Select all seniors
        function selectAll() {
            const checkboxes = document.querySelectorAll('.senior-checkbox');
            checkboxes.forEach(checkbox => {
                if (checkbox.closest('tr').style.display !== 'none') {
                    checkbox.checked = true;
                    const id = checkbox.dataset.id;
                    if (!selectedSeniors.has(id)) {
                        selectedSeniors.set(id, {
                            name: checkbox.dataset.name,
                            birthdate: checkbox.dataset.birthdate,
                            age: checkbox.dataset.age,
                            gender: checkbox.dataset.gender,
                            barangay: checkbox.dataset.barangay,
                            idNumber: checkbox.dataset.idNumber,
                            dateIssued: checkbox.dataset.dateIssued,
                            localControl: checkbox.dataset.localControl
                        });
                    }
                }
            });

            updateSelectedList();
            updateMasterCheckbox();
        }

        // Deselect all seniors
        function deselectAll() {
            selectedSeniors.clear();
            document.querySelectorAll('.senior-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            updateSelectedList();
            updateMasterCheckbox();
        }

        // Toggle master checkbox
        function toggleMasterCheckbox() {
            const masterCheckbox = document.getElementById('master-checkbox');
            
            if (masterCheckbox.checked) {
                selectAll();
            } else {
                deselectAll();
            }
        }

        // Update master checkbox state
        function updateMasterCheckbox() {
            const masterCheckbox = document.getElementById('master-checkbox');
            const checkboxes = document.querySelectorAll('.senior-checkbox:not([style*="display: none"])');
            const checkedCount = document.querySelectorAll('.senior-checkbox:checked').length;

            if (checkedCount === 0) {
                masterCheckbox.checked = false;
                masterCheckbox.indeterminate = false;
            } else if (checkedCount === checkboxes.length) {
                masterCheckbox.checked = true;
                masterCheckbox.indeterminate = false;
            } else {
                masterCheckbox.checked = false;
                masterCheckbox.indeterminate = true;
            }
        }

        // Preview IDs
        function previewIDs() {
            if (selectedSeniors.size === 0) {
                alert('Please select at least one senior citizen.');
                return;
            }

            // Convert Map to Array
            const seniorsArray = Array.from(selectedSeniors, ([id, data]) => ({
                id,
                ...data
            }));

            // Generate preview HTML
            const previewContent = document.getElementById('preview-content');
            previewContent.innerHTML = generatePreviewHTML(seniorsArray);

            // Update preview info
            document.getElementById('preview-count').textContent = seniorsArray.length;

            // Show modal
            document.getElementById('print-preview-modal').classList.remove('hidden');
        }

        // Generate preview HTML
        function generatePreviewHTML(seniorsArray) {
            const oscaHead = document.getElementById('osca-head').value;
            const municipalMayor = document.getElementById('municipal-mayor').value;
            const currentDate = document.getElementById('date-issued').value;
            const formattedCurrentDate = new Date(currentDate).toLocaleDateString('en-US', {
                month: '2-digit',
                day: '2-digit',
                year: 'numeric'
            });

            // Group into pages of 9
            const pages = [];
            for (let i = 0; i < seniorsArray.length; i += 9) {
                pages.push(seniorsArray.slice(i, i + 9));
            }

            totalPreviewPages = pages.length;
            const currentPage = pages[currentPreviewPage - 1] || [];

            // Create HTML for one page (9 IDs)
            let html = `
        <div class="print-page" style="width: 13in; height: 8.5in; transform: scale(0.7); transform-origin: top left;">
            <div class="grid grid-cols-3 grid-rows-3 gap-4 p-4">
        `;

            currentPage.forEach(senior => {
                const dob = senior.birthdate ? new Date(senior.birthdate).toLocaleDateString('en-US', {
                    month: '2-digit',
                    day: '2-digit',
                    year: 'numeric'
                }) : 'N/A';
                
                // Use existing date issued or current date for new IDs
                const dateIssued = senior.dateIssued && senior.dateIssued !== 'N/A' ? 
                    new Date(senior.dateIssued).toLocaleDateString('en-US', {
                        month: '2-digit',
                        day: '2-digit',
                        year: 'numeric'
                    }) : formattedCurrentDate;
                
                const idNumber = senior.idNumber && senior.idNumber !== 'N/A' ? senior.idNumber : 
                    senior.localControl ? senior.localControl : 
                    'PALUAN-' + senior.id.substring(0, 6);

                html += `
            <div class="id-card">
                <!-- Republic Header -->
                <div class="id-header">
                    <div style="font-size: 6pt;">Republic of the Philippines</div>
                    <div style="font-size: 6pt;">Office for Senior Citizens Affairs (OSCA)</div>
                    <div style="font-size: 6pt;">Paluan, Occidental Mindoro</div>
                </div>
                
                <!-- ID Content -->
                <div style="display: flex; margin-top: 3px;">
                    <!-- Photo Area -->
                    <div style="width: 40%; display: flex; flex-direction: column; align-items: center;">
                        <div class="id-photo" style="width: 45px; height: 45px;">
                            <span style="font-size: 4pt;">1x1</span>
                        </div>
                        <div style="text-align: center; margin-top: 2px;">
                            <div style="font-size: 4pt; font-weight: medium;">ID PIC</div>
                        </div>
                    </div>
                    
                    <!-- Info Area -->
                    <div style="width: 60%; padding-left: 4px;">
                        <div style="font-size: 4pt; font-weight: semibold;">Name:</div>
                        <div style="font-size: 5pt; font-weight: bold; margin-bottom: 2px; overflow: hidden; text-overflow: ellipsis;">
                            ${senior.name}
                        </div>
                        <div style="font-size: 4pt; font-weight: semibold;">Address:</div>
                        <div style="font-size: 5pt; margin-bottom: 2px; overflow: hidden; text-overflow: ellipsis;">
                            ${senior.barangay || ''}
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2px; margin-top: 3px;">
                            <div>
                                <div style="font-size: 3pt;">Date of Birth</div>
                                <div style="font-size: 4pt;">${dob}</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 3pt;">Sex</div>
                                <div style="font-size: 4pt;">${senior.gender === 'Male' ? 'M' : senior.gender === 'Female' ? 'F' : ''}</div>
                            </div>
                            <div>
                                <div style="font-size: 3pt;">Date Issued</div>
                                <div style="font-size: 4pt;">${dateIssued}</div>
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 4px;">
                            <div style="height: 12px; border-bottom: 1px solid #000; margin-bottom: 1px;"></div>
                            <div style="font-size: 3pt;">Signature/Thumbmark</div>
                        </div>
                        <div style="text-align: center; margin-top: 2px;">
                            <div style="font-size: 4pt; font-weight: medium;">
                                ID No. <span style="font-weight: bold;">${idNumber}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Non-Transferable Notice -->
                <div style="text-align: center; margin-top: 3px;">
                    <div style="font-size: 4pt; font-weight: bold; color: #dc2626;">
                        THIS CARD IS NON-TRANSFERABLE
                    </div>
                </div>
            </div>
        `;
            });

            // Fill empty spots
            for (let i = currentPage.length; i < 9; i++) {
                html += '<div class="border border-dashed border-gray-300 rounded p-2"></div>';
            }

            html += `
            </div>
            <div class="text-center mt-2 text-sm text-gray-600">
                Page ${currentPreviewPage} of ${totalPreviewPages} | Front Side (ID Information)
            </div>
        </div>
        `;

            return html;
        }

        // Generate PDF
        function generatePDF() {
            if (selectedSeniors.size === 0) {
                alert('Please select at least one senior citizen.');
                return;
            }

            // Prepare data for PDF generation
            const seniorsArray = Array.from(selectedSeniors, ([id, data]) => ({
                id,
                ...data
            }));

            const oscaHead = document.getElementById('osca-head').value;
            const municipalMayor = document.getElementById('municipal-mayor').value;
            const dateIssued = document.getElementById('date-issued').value;

            // Create form and submit to PHP script
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../php/generate_id_pdf.php';
            form.style.display = 'none';

            const seniorsInput = document.createElement('input');
            seniorsInput.type = 'hidden';
            seniorsInput.name = 'seniors';
            seniorsInput.value = JSON.stringify(seniorsArray);
            form.appendChild(seniorsInput);

            const oscaHeadInput = document.createElement('input');
            oscaHeadInput.type = 'hidden';
            oscaHeadInput.name = 'osca_head';
            oscaHeadInput.value = oscaHead;
            form.appendChild(oscaHeadInput);

            const mayorInput = document.createElement('input');
            mayorInput.type = 'hidden';
            mayorInput.name = 'municipal_mayor';
            mayorInput.value = municipalMayor;
            form.appendChild(mayorInput);

            const dateInput = document.createElement('input');
            dateInput.type = 'hidden';
            dateInput.name = 'date_issued';
            dateInput.value = dateIssued;
            form.appendChild(dateInput);

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        // Print IDs
        function printIDs() {
            if (selectedSeniors.size === 0) {
                alert('Please select at least one senior citizen.');
                return;
            }

            // Open print dialog
            window.print();
        }

        // Close preview modal
        function closePreview() {
            document.getElementById('print-preview-modal').classList.add('hidden');
        }

        // Print preview
        function printPreview() {
            const printWindow = window.open('', '_blank');
            const previewContent = document.getElementById('preview-content').innerHTML;
            
            printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Senior Citizen IDs - Print Preview</title>
            <style>
                @media print {
                    @page {
                        size: landscape;
                        margin: 0.5in;
                    }
                    body {
                        margin: 0;
                        padding: 0;
                    }
                    .print-page {
                        width: 13in;
                        height: 8.5in;
                        page-break-after: always;
                    }
                    .id-card {
                        border: 1px solid #000;
                        border-radius: 3px;
                        padding: 4px;
                        font-family: Arial, sans-serif;
                    }
                    .id-header {
                        text-align: center;
                        font-weight: bold;
                        margin-bottom: 3px;
                    }
                }
            </style>
        </head>
        <body>
            ${previewContent}
        </body>
        </html>
    `);
            
            printWindow.document.close();
            printWindow.focus();
            
            // Wait for content to load then print
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
        }
    </script>
</body>
</html>