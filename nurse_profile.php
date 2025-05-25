<?php
// config.php should be the first include; it handles session_start(), $conn, etc.
require_once 'config.php';

// Check if user is logged in and has the appropriate role.
// This function should call exit() if conditions are not met.
// checkRole(['admin', 'nurse']);

// // Get current user details safely
// $currentUser = getCurrentUser(); // This should return an array or null

// // If $currentUser is null (e.g., session expired, user not found after checkRole passed - unusual),
// // or if getCurrentUser() itself failed, then logout and exit.
// if (!$currentUser) {
//     error_log("nurse_profile.php: \$currentUser is null or false after getCurrentUser(). Attempting logout.");
//     logout(); // logout() also contains an exit().
//     exit();   // Explicit exit here as well.
// }

// SUPER DEFENSIVE CHECK: Ensure $currentUser is an array and has expected keys.
// This is to prevent "Trying to access array offset on value of type null" if prior exits failed.
// if (!is_array($currentUser) || !isset($currentUser['role']) || !isset($currentUser['user_id']) || !isset($currentUser['username'])) {
//     error_log("nurse_profile.php: Critical state - \$currentUser is not a valid array or missing expected keys after initial checks. Forcing logout and exit.");
//     // If we reach here, something is fundamentally wrong with session state or control flow.
//     // This indicates that checkRole() or the if(!$currentUser) block did not exit as expected.
//     // Display a simple error to the user, as rendering the full page is not safe.
//     echo "A critical session error occurred. Please log out and try logging in again. If the problem persists, contact support."; // Avoid further complex HTML rendering.
//     logout(); // Attempt to clear session and redirect.
//     exit();   // Final attempt to stop script.
// }

// If execution reaches here, $currentUser is confirmed to be a valid array 
// with 'role', 'user_id', and 'username' keys.

$message = '';
$error_message = '';

// Handle messages from GET parameters (e.g., after a redirect)
if (isset($_GET['message'])) {
    $message = sanitizeInput(urldecode($_GET['message']));
}
if (isset($_GET['error_message'])) {
    $error_message = sanitizeInput(urldecode($_GET['error_message']));
}

// Determine the nurse ID being viewed or edited
$target_nurse_id_from_db = null; // This is nurses.id (the primary key of the nurses table)
$current_nurse_data = null;      // Holds the profile data for the nurse being viewed/edited

// Accessing $currentUser['role'] and $currentUser['user_id'] is now safe.
if ($currentUser['role'] === 'admin' && isset($_GET['view'])) {
    $target_nurse_id_from_db = filter_input(INPUT_GET, 'view', FILTER_VALIDATE_INT);
} elseif ($currentUser['role'] === 'nurse') {
    // Nurse is viewing/editing their own profile. Get their nurses.id from their users.id.
    $target_nurse_id_from_db = getNurseInternalId($conn, $currentUser['user_id']);
}


// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_taken = false; // Flag to check if any POST action was processed

    if (isset($_POST['update_profile'])) {
        $action_taken = true;
        $firstName = sanitizeInput($_POST['first_name']);
        $lastName = sanitizeInput($_POST['last_name']);
        $department = sanitizeInput($_POST['department']);
        $phone = sanitizeInput($_POST['phone']);
        $qualifications = sanitizeInput($_POST['qualifications']);
        $shift_schedule = sanitizeInput($_POST['shift_schedule']);

        $editing_nurse_db_id_hidden = null; // The nurses.id from the hidden form field

        if ($currentUser['role'] === 'admin' && isset($_POST['editing_nurse_db_id_hidden'])) {
             $editing_nurse_db_id_hidden = filter_input(INPUT_POST, 'editing_nurse_db_id_hidden', FILTER_VALIDATE_INT);
        } elseif ($currentUser['role'] === 'nurse') {
            // For a nurse updating their own profile, target_nurse_id_from_db already holds their nurses.id (if profile exists)
            $editing_nurse_db_id_hidden = $target_nurse_id_from_db;
        }

        if ($editing_nurse_db_id_hidden) { // Updating existing nurse profile
            // Prepare statement
            $stmt = $conn->prepare("UPDATE nurses SET first_name = ?, last_name = ?, department = ?, phone = ?, qualifications = ?, shift_schedule = ? WHERE id = ? AND user_id = ?");

            // Determine the user_id to use for the WHERE clause to ensure authorization
            $user_id_for_update_check = null;
            if ($currentUser['role'] === 'nurse') {
                $user_id_for_update_check = $currentUser['user_id'];
            } elseif ($currentUser['role'] === 'admin') {
                // Admin needs to update based on the nurse's actual user_id linked to nurses.id
                $fetch_user_id_stmt = $conn->prepare("SELECT user_id FROM nurses WHERE id = ?");
                if($fetch_user_id_stmt) {
                    $fetch_user_id_stmt->bind_param("i", $editing_nurse_db_id_hidden);
                    $fetch_user_id_stmt->execute();
                    $result_user_id = $fetch_user_id_stmt->get_result();
                    if($row_user_id = $result_user_id->fetch_assoc()){
                        $user_id_for_update_check = $row_user_id['user_id'];
                    }
                    $fetch_user_id_stmt->close();
                }
            }
            
            if ($stmt && $user_id_for_update_check !== null) {
                $stmt->bind_param("ssssssii", $firstName, $lastName, $department, $phone, $qualifications, $shift_schedule, $editing_nurse_db_id_hidden, $user_id_for_update_check);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0 || $stmt->errno == 0) {
                         $message = "Profile updated successfully!";
                    } else {
                         $message = "No changes were made to the profile."; // Or "Profile already up-to-date."
                    }
                } else {
                    $error_message = "Error updating profile: " . htmlspecialchars($stmt->error);
                }
                $stmt->close();
            } elseif(!$stmt) {
                $error_message = "Database error (prepare update): " . htmlspecialchars($conn->error);
            } else {
                $error_message = "Could not verify user for update. Profile not updated.";
            }

        } elseif ($currentUser['role'] === 'nurse' && !$editing_nurse_db_id_hidden) { // Nurse creating profile for the first time
            $stmt = $conn->prepare("INSERT INTO nurses (user_id, first_name, last_name, department, phone, qualifications, shift_schedule) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("issssss", $currentUser['user_id'], $firstName, $lastName, $department, $phone, $qualifications, $shift_schedule);
                if ($stmt->execute()) {
                    $message = "Profile created successfully!";
                    $target_nurse_id_from_db = $conn->insert_id; // Update for subsequent display
                } else {
                    $error_message = "Error creating profile: " . htmlspecialchars($stmt->error);
                }
                $stmt->close();
            } else {
                 $error_message = "Database error (prepare insert): " . htmlspecialchars($conn->error);
            }
        } else {
             $error_message = "Could not determine nurse ID for update or invalid action.";
        }

    } elseif (isset($_POST['delete_nurse']) && $currentUser['role'] === 'admin') {
        $action_taken = true;
        $nurseId_to_delete = filter_input(INPUT_POST, 'nurse_id_to_delete', FILTER_VALIDATE_INT);
        if ($nurseId_to_delete) {
            $stmt = $conn->prepare("DELETE FROM nurses WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $nurseId_to_delete);
                if ($stmt->execute()) {
                    $message = "Nurse profile deleted successfully.";
                    header("Location: nurse_profile.php?message=" . urlencode($message));
                    exit();
                } else {
                    $error_message = "Error deleting nurse: " . htmlspecialchars($stmt->error);
                }
                $stmt->close();
            } else {
                 $error_message = "Database error (prepare delete): " . htmlspecialchars($conn->error);
            }
        } else {
            $error_message = "Invalid Nurse ID for deletion.";
        }
    }

    if ($action_taken) {
        $redirect_url = "nurse_profile.php";
        if (($currentUser['role'] === 'admin' && isset($_GET['view']) && $target_nurse_id_from_db) || ($currentUser['role'] === 'nurse' && $target_nurse_id_from_db) ) {
            $redirect_url .= "?view=" . $target_nurse_id_from_db;
        }

        $query_params = [];
        if ($message) $query_params[] = "message=" . urlencode($message);
        if ($error_message) $query_params[] = "error_message=" . urlencode($error_message);

        if (!empty($query_params)) {
            $redirect_url .= (strpos($redirect_url, '?') === false ? '?' : '&') . implode('&', $query_params);
        }
        header("Location: " . $redirect_url);
        exit();
    }
}

if ($target_nurse_id_from_db) {
    $stmt_nurse = $conn->prepare("SELECT n.*, u.username, u.email FROM nurses n JOIN users u ON n.user_id = u.id WHERE n.id = ?");
    if ($stmt_nurse) {
        $stmt_nurse->bind_param("i", $target_nurse_id_from_db);
        if ($stmt_nurse->execute()) {
            $result_nurse = $stmt_nurse->get_result();
            $current_nurse_data = $result_nurse->fetch_assoc();
            if (!$current_nurse_data && $currentUser['role'] === 'admin') {
                $error_message = "Nurse profile with ID " . htmlspecialchars($target_nurse_id_from_db) . " not found.";
            }
        } else {
            $error_message = "Database error (execute select nurse): " . htmlspecialchars($stmt_nurse->error);
        }
        $stmt_nurse->close();
    } else {
        $error_message = "Database error (prepare select nurse): " . htmlspecialchars($conn->error);
    }
}

$allNurses = [];
if ($currentUser['role'] === 'admin' && !$target_nurse_id_from_db) {
    $result_all_nurses = $conn->query("SELECT n.id, n.first_name, n.last_name, n.department, n.phone, u.username 
                                       FROM nurses n 
                                       JOIN users u ON n.user_id = u.id 
                                       ORDER BY n.last_name, n.first_name");
    if($result_all_nurses) {
        while($row = $result_all_nurses->fetch_assoc()){
            $allNurses[] = $row;
        }
        $result_all_nurses->free();
    } elseif ($conn->error && empty($error_message)) {
        $error_message = "Error fetching nurse list: " . htmlspecialchars($conn->error);
    }
}

$page_title = "Manage Nurses"; 
$form_title = "Nurse Profile Details"; 

if ($currentUser['role'] === 'nurse') {
    $page_title = $current_nurse_data ? "My Profile" : "Create My Profile";
    $form_title = $page_title;
} elseif ($currentUser['role'] === 'admin' && $target_nurse_id_from_db) {
    if ($current_nurse_data) {
        $page_title = "Edit Nurse: " . htmlspecialchars($current_nurse_data['first_name'] . ' ' . $current_nurse_data['last_name']);
        $form_title = $page_title;
    } else {
        $page_title = "Manage Nurse Profile"; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        .message { padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: center; }
        .message.success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
        .message.error { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
        .confirmation-dialog { display: none; position: fixed; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .confirmation-content { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; }
        .confirmation-buttons button { margin: 0 10px; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .confirm-yes { background-color: #ef4444; color: white; } /* Tailwind red-500 */
        .confirm-no { background-color: #d1d5db; } /* Tailwind gray-300 */
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                     <a href="dashboard.php" class="flex-shrink-0 flex items-center text-decoration-none">
                        <i class="fas fa-hospital text-blue-600 text-2xl mr-2"></i>
                        <span class="text-xl font-bold text-gray-800">Hospital</span>
                    </a>
                </div>
                <div class="hidden md:ml-6 md:flex md:items-center">
                    <div class="flex space-x-1">
                        <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                        <?php if(in_array($currentUser['role'], ['admin', 'doctor'])): ?>
                            <a href="doctor_profile.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'doctor_profile.php' ? 'bg-blue-100 text-blue-600' : ''; ?>">Doctors</a>
                        <?php endif; ?>
                        <a href="nurse_profile.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'nurse_profile.php' ? 'bg-blue-100 text-blue-600' : ''; ?>">Nurses</a>
                        <span class="text-gray-500 px-3 py-2 rounded-md text-sm font-medium">User: <?php echo htmlspecialchars($currentUser['username']); ?> (<?php echo htmlspecialchars(ucfirst($currentUser['role'])); ?>)</span>
                        <a href="logout.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Logout <i class="fas fa-sign-out-alt ml-1"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white shadow-xl rounded-lg p-6 md:p-8">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-6">
                <?php echo htmlspecialchars($page_title); ?>
            </h1>

            <?php if ($message): ?><div class="message success" role="alert"><?php echo $message; ?></div><?php endif; ?>
            <?php if ($error_message): ?><div class="message error" role="alert"><?php echo $error_message; ?></div><?php endif; ?>
            
            <?php if ($currentUser['role'] === 'admin' && !$target_nurse_id_from_db && empty($error_message)): ?>
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">All Registered Nurses</h2>
                    <?php if (!empty($allNurses)): ?>
                    <div class="overflow-x-auto shadow-md rounded-lg">
                        <table class="min-w-full bg-white">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 border-b border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                                    <th class="py-3 px-4 border-b border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Username (User)</th>
                                    <th class="py-3 px-4 border-b border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Department</th>
                                    <th class="py-3 px-4 border-b border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Phone</th>
                                    <th class="py-3 px-4 border-b border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($allNurses as $nrs): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-4 whitespace-nowrap"><?php echo htmlspecialchars($nrs['first_name'] . ' ' . $nrs['last_name']); ?></td>
                                    <td class="py-3 px-4 whitespace-nowrap"><?php echo htmlspecialchars($nrs['username']); ?></td>
                                    <td class="py-3 px-4 whitespace-nowrap"><?php echo htmlspecialchars($nrs['department']); ?></td>
                                    <td class="py-3 px-4 whitespace-nowrap"><?php echo htmlspecialchars($nrs['phone']); ?></td>
                                    <td class="py-3 px-4 whitespace-nowrap text-sm font-medium">
                                        <a href="nurse_profile.php?view=<?php echo $nrs['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3"><i class="fas fa-eye mr-1"></i>View/Edit</a>
                                        <form action="nurse_profile.php" method="POST" class="inline delete-form">
                                            <input type="hidden" name="nurse_id_to_delete" value="<?php echo $nrs['id']; ?>">
                                            <button type="submit" name="delete_nurse" class="text-red-600 hover:text-red-900 delete-button"><i class="fas fa-trash mr-1"></i>Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                         <p class="text-center text-gray-500">No nurses found.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php 
            $show_form = false;
            if ($currentUser['role'] === 'nurse') { 
                $show_form = true;
            } elseif ($currentUser['role'] === 'admin' && $target_nurse_id_from_db) { 
                if ($current_nurse_data) { 
                    $show_form = true;
                }
            }
            ?>

            <?php if ($show_form): ?>
                <div class="mt-6">
                     <h2 class="text-xl font-semibold text-gray-700 mb-4"><?php echo htmlspecialchars($form_title); ?></h2>
                     <?php if($current_nurse_data && isset($current_nurse_data['username'])): ?>
                        <p class="mb-2 text-sm text-gray-600">
                            Managing profile for user: <strong><?php echo htmlspecialchars($current_nurse_data['username']); ?></strong>
                            (Email: <?php echo htmlspecialchars($current_nurse_data['email'] ?? 'N/A'); ?>)
                        </p>
                     <?php elseif($currentUser['role'] === 'nurse'): ?>
                         <p class="mb-2 text-sm text-gray-600">
                            User: <strong><?php echo htmlspecialchars($currentUser['username']); ?></strong>
                            (Email: <?php echo htmlspecialchars($currentUser['email'] ?? 'N/A'); ?>)
                        </p>
                     <?php endif; ?>

                    <form method="POST" action="nurse_profile.php<?php echo ($currentUser['role'] === 'admin' && $target_nurse_id_from_db) ? '?view='.$target_nurse_id_from_db : ''; ?>" class="mt-2">
                        <?php 
                        if ($currentUser['role'] === 'admin' && $current_nurse_data && $target_nurse_id_from_db): ?>
                            <input type="hidden" name="editing_nurse_db_id_hidden" value="<?php echo htmlspecialchars($target_nurse_id_from_db); ?>">
                        <?php endif; ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                                <input type="text" id="first_name" name="first_name" required
                                    value="<?php echo htmlspecialchars($current_nurse_data['first_name'] ?? ''); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" id="last_name" name="last_name" required
                                    value="<?php echo htmlspecialchars($current_nurse_data['last_name'] ?? ''); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Department <span class="text-red-500">*</span></label>
                            <input type="text" id="department" name="department" required
                                value="<?php echo htmlspecialchars($current_nurse_data['department'] ?? ''); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div class="mb-6">
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input type="tel" id="phone" name="phone"
                                value="<?php echo htmlspecialchars($current_nurse_data['phone'] ?? ''); ?>"
                                placeholder="e.g., 01XXXXXXXXX"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                         <div class="mb-6">
                            <label for="shift_schedule" class="block text-sm font-medium text-gray-700 mb-1">Shift Schedule</label>
                            <input type="text" id="shift_schedule" name="shift_schedule" placeholder="e.g., Day, Night, Rotating"
                                value="<?php echo htmlspecialchars($current_nurse_data['shift_schedule'] ?? ''); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div class="mb-6">
                            <label for="qualifications" class="block text-sm font-medium text-gray-700 mb-1">Qualifications</label>
                            <textarea id="qualifications" name="qualifications" rows="4"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($current_nurse_data['qualifications'] ?? ''); ?></textarea>
                        </div>

                        <div>
                            <button type="submit" name="update_profile" 
                                    class="w-full md:w-auto inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-save mr-2"></i>
                                <?php echo ($current_nurse_data && $target_nurse_id_from_db) ? 'Update Profile' : 'Create Profile'; // Adjusted condition slightly ?>
                            </button>
                             <?php if ($currentUser['role'] === 'admin' && $target_nurse_id_from_db): ?>
                                <a href="nurse_profile.php" class="ml-4 inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Back to Nurse List
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php elseif ($currentUser['role'] === 'admin' && $target_nurse_id_from_db && !$current_nurse_data && !$error_message) : ?>
                <p class="text-center text-gray-600">
                    The requested nurse profile (ID: <?php echo htmlspecialchars($target_nurse_id_from_db); ?>) could not be found.
                    <a href="nurse_profile.php" class="text-indigo-600 hover:underline">Return to the nurse list</a>.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div id="customConfirmDialog" class="confirmation-dialog">
        <div class="confirmation-content">
            <p id="confirmMessage" class="mb-4 text-lg">Are you sure?</p>
            <div class="confirmation-buttons">
                <button id="confirmYesButton" class="confirm-yes">Yes, Delete</button>
                <button id="confirmNoButton" class="confirm-no">Cancel</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteForms = document.querySelectorAll('.delete-form');
        const customConfirmDialog = document.getElementById('customConfirmDialog');
        const confirmMessageElement = document.getElementById('confirmMessage'); 
        const confirmYesButton = document.getElementById('confirmYesButton');
        const confirmNoButton = document.getElementById('confirmNoButton');
        let currentForm = null;

        deleteForms.forEach(form => {
            form.addEventListener('submit', function (event) {
                event.preventDefault(); 
                currentForm = this; 
                const nurseNameElement = this.closest('tr')?.cells[0];
                const nurseName = nurseNameElement ? nurseNameElement.textContent.trim() : 'this nurse';
                confirmMessageElement.textContent = `Are you sure you want to delete the profile for ${nurseName}? This action cannot be undone.`;
                customConfirmDialog.style.display = 'flex';
            });
        });

        confirmYesButton.addEventListener('click', function () {
            if (currentForm) {
                currentForm.submit(); 
            }
            customConfirmDialog.style.display = 'none';
        });

        confirmNoButton.addEventListener('click', function () {
            currentForm = null;
            customConfirmDialog.style.display = 'none';
        });

        customConfirmDialog.addEventListener('click', function(event) {
            if (event.target === customConfirmDialog) { 
                currentForm = null;
                customConfirmDialog.style.display = 'none';
            }
        });
    });
    </script>
<?php 
if ($conn) { // Check if $conn is a valid resource/object before closing
    $conn->close(); 
}
?>
</body>
</html>
