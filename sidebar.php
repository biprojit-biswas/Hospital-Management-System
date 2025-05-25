<div class="sidebar">
    <h2>HMS Dashboard</h2>
    <ul>
        <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">Home</a></li>
        <li><a href="patients.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'patients.php' ? 'active' : ''; ?>">Patients</a></li>
        <li><a href="doctors.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'doctors.php' ? 'active' : ''; ?>">Doctors Mgt (Admin)</a></li>
        <li><a href="doctor_profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'doctor_profile.php' ? 'active' : ''; ?>">Doctor Profile</a></li>
        <li><a href="nurse_profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'nurse_profile.php' ? 'active' : ''; ?>">Nurses</a></li>
        <li><a href="appointments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>">Appointments</a></li>
        <li><a href="prescriptions.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'prescriptions.php' ? 'active' : ''; ?>">Prescriptions</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>