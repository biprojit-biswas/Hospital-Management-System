<?php
require_once 'config.php'; // Ensures session_start() and $conn are available
checkRole(['admin', 'doctor']); // Ensures only admin or doctor can access

$message = '';
$error_message = '';

// Determine if admin is viewing a specific doctor or doctor is viewing their own profile
$view_doctor_id_from_get = null; // This is the ID from the 'doctors' table
$current_doctor_data = null; // Holds the doctor's profile data for the form
$schedules = []; // Holds schedules for the current doctor

if ($_SESSION['user_role'] === 'admin' && isset($_GET['view'])) {
    $view_doctor_id_from_get = filter_input(INPUT_GET, 'view', FILTER_VALIDATE_INT);
} elseif ($_SESSION['user_role'] === 'doctor') {
    // Doctor views their own profile. Need to get their 'doctors.id' from their 'users.id'
    $stmt_get_doc_id = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmt_get_doc_id->bind_param("i", $_SESSION['user_id']);
    $stmt_get_doc_id->execute();
    $result_doc_id = $stmt_get_doc_id->get_result();
    if ($doc_id_row = $result_doc_id->fetch_assoc()) {
        $view_doctor_id_from_get = $doc_id_row['id'];
    }
    $stmt_get_doc_id->close();
}


// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_doctor_id = null; // The doctors.id being acted upon

    if (isset($_POST['update_profile'])) {
        $firstName = sanitizeInput($_POST['first_name']);
        $lastName = sanitizeInput($_POST['last_name']);
        $specialization = sanitizeInput($_POST['specialization']);
        $phone = sanitizeInput($_POST['phone']);
        $qualifications = sanitizeInput($_POST['qualifications']); // Includes bio for now
        // available_days and available_hours are now part of the main profile form if not using doctor_schedule
        $available_days = sanitizeInput($_POST['available_days']);
        $available_hours = sanitizeInput($_POST['available_hours']);


        if ($_SESSION['user_role'] === 'admin' && isset($_POST['editing_doctor_id'])) {
            $action_doctor_id = filter_input(INPUT_POST, 'editing_doctor_id', FILTER_VALIDATE_INT);
        } elseif ($_SESSION['user_role'] === 'doctor' && $view_doctor_id_from_get) { // doctor editing their own profile
             $action_doctor_id = $view_doctor_id_from_get;
        }
        
        if ($action_doctor_id) {
            // Check if doctor record exists for this doctors.id
            $stmt_check = $conn->prepare("SELECT id, user_id FROM doctors WHERE id = ?");
            $stmt_check->bind_param("i", $action_doctor_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $existing_doctor = $result_check->fetch_assoc();
            $stmt_check->close();

            if ($existing_doctor) {
                 // Update existing doctor. Added available_days, available_hours
                $stmt_update = $conn->prepare("UPDATE doctors SET first_name = ?, last_name = ?, specialization = ?, phone = ?, qualifications = ?, available_days = ?, available_hours = ? WHERE id = ?");
                $stmt_update->bind_param("sssssssi", $firstName, $lastName, $specialization, $phone, $qualifications, $available_days, $available_hours, $action_doctor_id);
                if ($stmt_update->execute()) {
                    $message = "Profile updated successfully!";
                } else {
                    $error_message = "Error updating profile: " . $stmt_update->error;
                }
                $stmt_update->close();
            } else if ($_SESSION['user_role'] === 'doctor' && !$existing_doctor) { // Doctor creating profile first time
                // Create new doctor profile if it doesn't exist for this user_id
                // This assumes the 'doctors.id' (view_doctor_id_from_get) might not exist yet if it's a new doctor profile tied to a user
                $stmt_insert = $conn->prepare("INSERT INTO doctors (user_id, first_name, last_name, specialization, phone, qualifications, available_days, available_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_insert->bind_param("isssssss", $_SESSION['user_id'], $firstName, $lastName, $specialization, $phone, $qualifications, $available_days, $available_hours);
                 if ($stmt_insert->execute()) {
                    $message = "Profile created successfully!";
                    $view_doctor_id_from_get = $conn->insert_id; // Get the new doctor ID
                } else {
                    $error_message = "Error creating profile: " . $stmt_insert->error;
                }
                $stmt_insert->close();
            } else {
                 $error_message = "Doctor profile not found for update.";
            }
        } else {
            $error_message = "Could not determine doctor ID for profile update.";
        }

    } elseif (isset($_POST['update_schedule']) && $view_doctor_id_from_get) {
        // This part requires the `doctor_schedule` table.
        $day = sanitizeInput($_POST['day']);
        $startTime = sanitizeInput($_POST['start_time']);
        $endTime = sanitizeInput($_POST['end_time']);
        $isAvailable = isset($_POST['is_available']) ? 1 : 0;
        
        $stmt_check_schedule = $conn->prepare("SELECT id FROM doctor_schedule WHERE doctor_id = ? AND day_of_week = ?");
        $stmt_check_schedule->bind_param("is", $view_doctor_id_from_get, $day);
        $stmt_check_schedule->execute();
        $result_schedule_check = $stmt_check_schedule->get_result();
        $stmt_check_schedule->close();
        
        if ($result_schedule_check->num_rows > 0) {
            $stmt_schedule = $conn->prepare("UPDATE doctor_schedule SET start_time = ?, end_time = ?, is_available = ? WHERE doctor_id = ? AND day_of_week = ?");
            $stmt_schedule->bind_param("ssiis", $startTime, $endTime, $isAvailable, $view_doctor_id_from_get, $day);
        } else {
            $stmt_schedule = $conn->prepare("INSERT INTO doctor_schedule (doctor_id, day_of_week, start_time, end_time, is_available) VALUES (?, ?, ?, ?, ?)");
            $stmt_schedule->bind_param("isssi", $view_doctor_id_from_get, $day, $startTime, $endTime, $isAvailable);
        }
        if($stmt_schedule->execute()){
            $message = "Schedule updated successfully!";
        } else {
            $error_message = "Error updating schedule: " . $stmt_schedule->error . ". Make sure doctor_schedule table exists.";
        }
        $stmt_schedule->close();

    } elseif (isset($_POST['delete_doctor']) && $_SESSION['user_role'] === 'admin') {
        $doctorId_to_delete = filter_input(INPUT_POST, 'doctor_id_to_delete', FILTER_VALIDATE_INT);
        if ($doctorId_to_delete) {
            // Also consider deleting related user from `users` table or deactivating them.
            // And deleting from `doctor_schedule` table.
            $conn->query("DELETE FROM doctor_schedule WHERE doctor_id = $doctorId_to_delete"); // Delete schedules first
            
            $stmt_delete = $conn->prepare("DELETE FROM doctors WHERE id = ?");
            $stmt_delete->bind_param("i", $doctorId_to_delete);
            if($stmt_delete->execute()){
                $message = "Doctor profile and associated schedules deleted.";
                 // Optionally, redirect to the main doctors list
                header("Location: doctors.php?message=" . urlencode($message));
                exit();
            } else {
                $error_message = "Error deleting doctor: " . $stmt_delete->error;
            }
            $stmt_delete->close();
        }
    }
    // After POST, redirect to GET to prevent resubmission and show messages
    $redirect_url = "doctor_profile.php";
    if ($view_doctor_id_from_get && $_SESSION['user_role'] === 'admin') {
        $redirect_url .= "?view=" . $view_doctor_id_from_get;
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


// Get doctor data for display/editing if $view_doctor_id_from_get is set
if ($view_doctor_id_from_get) {
    $stmt_doc = $conn->prepare("SELECT d.*, u.username, u.email FROM doctors d LEFT JOIN users u ON d.user_id = u.id WHERE d.id = ?");
    $stmt_doc->bind_param("i", $view_doctor_id_from_get);
    $stmt_doc->execute();
    $result_doc = $stmt_doc->get_result();
    $current_doctor_data = $result_doc->fetch_assoc();
    $stmt_doc->close();
    
    if ($current_doctor_data) {
        // Fetch schedules if doctor_schedule table exists and is used
        $stmt_sched = $conn->prepare("SELECT * FROM doctor_schedule WHERE doctor_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')");
        $stmt_sched->bind_param("i", $current_doctor_data['id']);
        if ($stmt_sched->execute()) { // Check if execute was successful (table might not exist)
            $result_sched = $stmt_sched->get_result();
            $schedules = $result_sched->fetch_all(MYSQLI_ASSOC);
        } else {
            // $error_message .= " Note: Could not fetch schedule details (doctor_schedule table might be missing).";
        }
        $stmt_sched->close();
    } else {
        if ($_SESSION['user_role'] === 'admin') $error_message = "Doctor profile not found.";
        // If doctor is viewing and no profile, they can create one.
    }
}

// For Admin: Get all doctors list (if not viewing a specific one)
$allDoctors = [];
if ($_SESSION['user_role'] === 'admin' && !$view_doctor_id_from_get) {
    // This part is typically on doctors.php, but can be here if page serves dual purpose
    $result_all_docs = $conn->query("SELECT d.id, d.first_name, d.last_name, d.specialization, d.phone, u.username 
                                     FROM doctors d 
                                     JOIN users u ON d.user_id = u.id 
                                     ORDER BY d.last_name, d.first_name");
    if($result_all_docs) $allDoctors = $result_all_docs->fetch_all(MYSQLI_ASSOC);
}

// Display messages from GET parameters
if(isset($_GET['message'])) $message = htmlspecialchars(urldecode($_GET['message']));
if(isset($_GET['error_message'])) $error_message = htmlspecialchars(urldecode($_GET['error_message']));

$page_title = "Doctor Profile";
if ($_SESSION['user_role'] === 'admin' && $view_doctor_id_from_get && $current_doctor_data) {
    $page_title = "Edit Doctor: " . htmlspecialchars($current_doctor_data['first_name'] . ' ' . $current_doctor_data['last_name']);
} elseif ($_SESSION['user_role'] === 'doctor' && $current_doctor_data) {
    $page_title = "My Profile";
} elseif ($_SESSION['user_role'] === 'doctor' && !$current_doctor_data) {
    $page_title = "Create My Profile";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex-shrink-0 flex items-center">
                        <i class="fas fa-hospital text-blue-500 text-2xl mr-2"></i>
                        <span class="text-xl font-bold text-gray-800">Hospital</span>
                    </a>
                </div>
                <div class="hidden md:ml-6 md:flex md:items-center">
                    <div class="flex space-x-4">
                        <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="doctors.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Manage Doctors</a>
                        <?php endif; ?>
                        <a href="doctor_profile.php" class="bg-blue-100 text-blue-600 px-3 py-2 rounded-md text-sm font-medium">My Profile</a>
                         <?php if (in_array($_SESSION['user_role'], ['admin', 'nurse'])): ?>
                            <a href="nurse_profile.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Nurses</a>
                        <?php endif; ?>
                        <a href="logout.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white shadow rounded-lg p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">
                <?php echo $page_title; ?>
            </h1>

            <?php if ($message): ?><div class="mb-4 p-3 bg-green-100 text-green-700 rounded"><?php echo $message; ?></div><?php endif; ?>
            <?php if ($error_message): ?><div class="mb-4 p-3 bg-red-100 text-red-700 rounded"><?php echo $error_message; ?></div><?php endif; ?>
            
            <?php // Form for doctor to edit own profile or admin to edit a specific doctor's profile ?>
            <?php if (($_SESSION['user_role'] === 'doctor') || ($_SESSION['user_role'] === 'admin' && $view_doctor_id_from_get)): ?>
                <?php if ($current_doctor_data || ($_SESSION['user_role'] === 'doctor' && !$current_doctor_data) ): // Show form if data exists or if doctor is creating new profile ?>
                <div class="mb-8">
                    <h2 class="text-xl font-semibold mb-4">Doctor Information</h2>
                    <form method="POST" action="doctor_profile.php<?php echo $view_doctor_id_from_get ? '?view='.$view_doctor_id_from_get : ''; ?>">
                         <?php if ($_SESSION['user_role'] === 'admin' && $view_doctor_id_from_get): ?>
                            <input type="hidden" name="editing_doctor_id" value="<?php echo $view_doctor_id_from_get; ?>">
                        <?php endif; ?>
                        User: <?php echo htmlspecialchars($current_doctor_data['username'] ?? ($_SESSION['username'] ?? 'N/A')); ?> (Email: <?php echo htmlspecialchars($current_doctor_data['email'] ?? ($_SESSION['email'] ?? 'N/A')); ?>)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 mt-2">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                <input type="text" id="first_name" name="first_name" required
                                    value="<?php echo htmlspecialchars($current_doctor_data['first_name'] ?? ''); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                <input type="text" id="last_name" name="last_name" required
                                    value="<?php echo htmlspecialchars($current_doctor_data['last_name'] ?? ''); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="specialization" class="block text-sm font-medium text-gray-700 mb-1">Specialization</label>
                            <input type="text" id="specialization" name="specialization" required
                                value="<?php echo htmlspecialchars($current_doctor_data['specialization'] ?? ''); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div class="mb-4">
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input type="tel" id="phone" name="phone" required
                                value="<?php echo htmlspecialchars($current_doctor_data['phone'] ?? ''); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div class="mb-4">
                            <label for="qualifications" class="block text-sm font-medium text-gray-700 mb-1">Qualifications & Bio</label>
                            <textarea id="qualifications" name="qualifications" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($current_doctor_data['qualifications'] ?? ''); ?></textarea>
                        </div>
                         <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="available_days" class="block text-sm font-medium text-gray-700 mb-1">Available Days</label>
                                <input type="text" id="available_days" name="available_days" placeholder="e.g., Monday,Wednesday,Friday"
                                    value="<?php echo htmlspecialchars($current_doctor_data['available_days'] ?? ''); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="available_hours" class="block text-sm font-medium text-gray-700 mb-1">Available Hours</label>
                                <input type="text" id="available_hours" name="available_hours" placeholder="e.g., 09:00-17:00"
                                    value="<?php echo htmlspecialchars($current_doctor_data['available_hours'] ?? ''); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <button type="submit" name="update_profile" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <?php echo ($current_doctor_data) ? 'Update Profile' : 'Create Profile'; ?>
                        </button>
                        <?php if ($_SESSION['user_role'] === 'admin' && $current_doctor_data): ?>
                             <a href="doctors.php" class="ml-4 text-gray-600 hover:text-gray-800">Back to Doctors List</a>
                        <?php endif; ?>
                    </form>
                </div>
                <?php endif; // End show form condition ?>

                <?php if($current_doctor_data): // Schedule can only be managed if profile exists ?>
                <div>
                    <h2 class="text-xl font-semibold mb-4 mt-8">Weekly Schedule (Using `doctor_schedule` Table)</h2>
                     <p class="text-sm text-gray-600 mb-2">Ensure `doctor_schedule` table is created in your database for this section to work.</p>
                    
                    <?php if ($_SESSION['user_role'] === 'doctor' || ($_SESSION['user_role'] === 'admin' && $view_doctor_id_from_get)): ?>
                        <form method="POST" action="doctor_profile.php<?php echo $view_doctor_id_from_get ? '?view='.$view_doctor_id_from_get : ''; ?>" class="mb-6 p-4 border rounded-md">
                            <h3 class="text-lg font-medium mb-2">Add/Update Day Schedule</h3>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                <div>
                                    <label for="day" class="block text-sm font-medium text-gray-700 mb-1">Day</label>
                                    <select id="day" name="day" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="Monday">Monday</option>
                                        <option value="Tuesday">Tuesday</option>
                                        <option value="Wednesday">Wednesday</option>
                                        <option value="Thursday">Thursday</option>
                                        <option value="Friday">Friday</option>
                                        <option value="Saturday">Saturday</option>
                                        <option value="Sunday">Sunday</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                                    <input type="time" id="start_time" name="start_time" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                                    <input type="time" id="end_time" name="end_time"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div class="flex items-end">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="is_available" name="is_available" checked value="1"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="is_available" class="ml-2 block text-sm text-gray-700">Available</label>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="update_schedule" class="bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                Set Schedule for Day
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Day</th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Start Time</th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">End Time</th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($schedules) > 0): ?>
                                    <?php foreach ($schedules as $schedule): ?>
                                        <tr>
                                            <td class="py-2 px-4 border-b border-gray-200"><?php echo htmlspecialchars($schedule['day_of_week']); ?></td>
                                            <td class="py-2 px-4 border-b border-gray-200"><?php echo $schedule['start_time'] ? htmlspecialchars(date('h:i A', strtotime($schedule['start_time']))) : 'N/A'; ?></td>
                                            <td class="py-2 px-4 border-b border-gray-200"><?php echo $schedule['end_time'] ? htmlspecialchars(date('h:i A', strtotime($schedule['end_time']))) : 'N/A'; ?></td>
                                            <td class="py-2 px-4 border-b border-gray-200">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $schedule['is_available'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                    <?php echo $schedule['is_available'] ? 'Available' : 'Not Available'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="py-2 px-4 border-b border-gray-200 text-center text-gray-500">No detailed weekly schedule set up. You can add entries above or manage general availability in the profile section.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; // End schedule if profile exists ?>
            <?php endif; // End main form/schedule view condition ?>


            <?php if ($_SESSION['user_role'] === 'admin' && $current_doctor_data): ?>
                <div class="mt-8 pt-4 border-t">
                    <h2 class="text-xl font-semibold text-red-600 mb-4">Delete Doctor Profile</h2>
                    <form method="POST" action="doctor_profile.php" onsubmit="return confirm('Are you sure you want to permanently delete this doctor? This will also remove their schedules and might affect related appointments/prescriptions records.');">
                        <input type="hidden" name="doctor_id_to_delete" value="<?php echo $current_doctor_data['id']; ?>">
                        <button type="submit" name="delete_doctor" class="bg-red-500 text-white py-2 px-4 rounded-md hover:bg-red-700">
                            Delete This Doctor Profile
                        </button>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </div>
    <?php $conn->close(); ?>
</body>
</html>