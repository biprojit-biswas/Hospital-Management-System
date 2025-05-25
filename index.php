<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Hospital Management System</title>
    <!--
      The long script URLs from Kaspersky are not standard and might be specific to your local environment's security software.
      They are not required for the general functionality of the HTML/Tailwind/JS.
      If they are causing issues or are not needed for others running the code, they can be removed.
      For this example, I am keeping the structure but commenting out the Kaspersky specific script and link tags
      as they are excessively long and not universally applicable.
    -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
      rel="stylesheet"
    />
    <style>
      /* Custom styles */
      .hero-image {
        background-image: linear-gradient(
            rgba(0, 0, 0, 0.5),
            rgba(0, 0, 0, 0.5)
          ),
          url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="%23f0f9ff"/><path d="M30,20 L70,20 L70,40 L80,40 L80,60 L70,60 L70,80 L30,80 L30,60 L20,60 L20,40 L30,40 Z" fill="%233b82f6" stroke="%231e40af" stroke-width="2"/><circle cx="50" cy="50" r="15" fill="%23ffffff"/><path d="M50,40 L50,60 M40,50 L60,50" stroke="%233b82f6" stroke-width="3"/></svg>');
        background-size: cover;
        background-position: center;
      }

      .feature-icon {
        width: 80px;
        height: 80px;
        background-color: #3b82f6; /* Tailwind blue-500 */
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: white;
        font-size: 32px;
      }
    </style>
  </head>
  <body class="font-sans bg-gray-50">
    <nav class="bg-white shadow-lg" id="navbar">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <div class="flex-shrink-0 flex items-center">
              <i class="fas fa-hospital text-blue-500 text-2xl mr-2"></i>
              <span class="text-xl font-bold text-gray-800">Hospital</span>
            </div>
          </div>

          <div class="flex items-center mx-4 flex-1 max-w-md">
            <div class="relative w-full">
              <div
                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
              >
                <i class="fas fa-search text-gray-400"></i>
              </div>
              <input
                type="text"
                id="searchInput"
                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                placeholder="Search..."
              />
            </div>
          </div>

          <div class="hidden md:ml-6 md:flex md:items-center">
            <div class="flex space-x-1" id="navMenu">
              </div>
             <div class="ml-4 flex items-center space-x-2" id="authSection">
                <a href="login.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                <a href="register.php" class="text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 px-3 py-2 rounded-md">Register</a>
            </div>
          </div>

          <div class="md:hidden flex items-center">
            <button
              type="button"
              id="mobileMenuButton"
              class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
              aria-expanded="false"
            >
              <span class="sr-only">Open main menu</span>
              <i class="fas fa-bars"></i> </button>
          </div>
        </div>
      </div>

      <div class="md:hidden hidden" id="mobileMenu">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3" id="mobileNavMenu">
          </div>
        <div class="pt-2 pb-3 px-2 space-y-1 sm:px-3 border-t border-gray-200" id="mobileAuthSection">
             <a href="login.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50">Login</a>
             <a href="register.php" class="block px-3 py-2 rounded-md text-base font-medium text-white bg-blue-500 hover:bg-blue-600">Register</a>
        </div>
      </div>
    </nav>

    <div class="hero-image bg-blue-500 text-white py-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-6">
          Advanced Hospital Management System
        </h1>
        <p class="text-xl md:text-2xl mb-8">
          Streamlining healthcare operations for better patient care.
        </p>
        <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4">
          <a
            href="register.php" 
            class="bg-white text-blue-600 px-6 py-3 rounded-lg font-medium hover:bg-gray-100 transition duration-300"
            >Get Started</a
          >
          <a
            href="#features"
            class="bg-transparent border-2 border-white px-6 py-3 rounded-lg font-medium hover:bg-white hover:text-blue-600 transition duration-300"
            >Learn More</a
          >
        </div>
      </div>
    </div>

    <div id="features" class="py-16 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900 mb-4">
            Our Comprehensive Solutions
          </h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Designed to meet all your healthcare management needs.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div
            class="bg-gray-50 p-6 rounded-lg shadow-sm hover:shadow-md transition duration-300"
          >
            <div class="feature-icon">
              <i class="fas fa-user-md"></i>
            </div>
            <h3 class="text-xl font-semibold text-center mb-3">
              Doctor Management
            </h3>
            <p class="text-gray-600 text-center">
              Efficiently manage doctor schedules, specialties, and patient
              assignments with our intuitive system.
            </p>
          </div>

          <div
            class="bg-gray-50 p-6 rounded-lg shadow-sm hover:shadow-md transition duration-300"
          >
            <div class="feature-icon">
              <i class="fas fa-procedures"></i>
            </div>
            <h3 class="text-xl font-semibold text-center mb-3">Patient Care</h3>
            <p class="text-gray-600 text-center">
              Comprehensive patient records, history tracking, and personalized
              care plans all in one place.
            </p>
          </div>

          <div
            class="bg-gray-50 p-6 rounded-lg shadow-sm hover:shadow-md transition duration-300"
          >
            <div class="feature-icon">
              <i class="fas fa-calendar-check"></i>
            </div>
            <h3 class="text-xl font-semibold text-center mb-3">
              Appointment System
            </h3>
            <p class="text-gray-600 text-center">
              Streamlined appointment scheduling with automated reminders for
              both patients and staff.
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-blue-600 text-white py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
          <div>
            <div class="text-4xl font-bold mb-2">250+</div>
            <div class="text-lg">Healthcare Partners</div>
          </div>
          <div>
            <div class="text-4xl font-bold mb-2">1M+</div>
            <div class="text-lg">Patients Served</div>
          </div>
          <div>
            <div class="text-4xl font-bold mb-2">24/7</div>
            <div class="text-lg">Support Available</div>
          </div>
          <div>
            <div class="text-4xl font-bold mb-2">99.9%</div>
            <div class="text-lg">System Uptime</div>
          </div>
        </div>
      </div>
    </div>

    <div class="py-16 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900 mb-4">
            What Our Clients Say
          </h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Trusted by healthcare professionals worldwide.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div class="bg-white p-6 rounded-lg shadow-sm">
            <div class="flex items-center mb-4">
              <div
                class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mr-4"
              >
                <i class="fas fa-user text-blue-500 text-xl"></i>
              </div>
              <div>
                <h4 class="font-semibold">Dr. Sarah Johnson</h4>
                <p class="text-gray-600 text-sm">Chief Medical Officer</p>
              </div>
            </div>
            <p class="text-gray-700">
              "This system has transformed how we manage patient care. The
              intuitive interface saves us hours each week, allowing us to focus
              more on our patients."
            </p>
          </div>

          <div class="bg-white p-6 rounded-lg shadow-sm">
            <div class="flex items-center mb-4">
              <div
                class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mr-4"
              >
                <i class="fas fa-user text-blue-500 text-xl"></i>
              </div>
              <div>
                <h4 class="font-semibold">Michael Rodriguez</h4>
                <p class="text-gray-600 text-sm">Hospital Administrator</p>
              </div>
            </div>
            <p class="text-gray-700">
              "The reporting features alone have helped us identify
              inefficiencies and improve our operations significantly. The
              support team is incredibly responsive."
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-blue-700 text-white py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold mb-6">
          Ready to Transform Your Healthcare Management?
        </h2>
        <p class="text-xl mb-8 max-w-3xl mx-auto">
          Join thousands of healthcare providers who trust our system to deliver
          exceptional patient care.
        </p>
        <a
          href="register.php" 
          class="inline-block bg-white text-blue-700 px-8 py-3 rounded-lg font-medium hover:bg-gray-100 transition duration-300"
          >Request a Demo</a
        >
      </div>
    </div>

    <footer class="bg-gray-800 text-white pt-12 pb-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
          <div>
            <h3 class="text-lg font-semibold mb-4">Hospital</h3>
            <p class="text-gray-400">
              Leading healthcare management solutions for modern medical
              facilities.
            </p>
            <div class="flex space-x-4 mt-4">
              <a href="#" class="text-gray-400 hover:text-white" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
              <a href="#" class="text-gray-400 hover:text-white" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
              <a href="#" class="text-gray-400 hover:text-white" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
              <a href="#" class="text-gray-400 hover:text-white" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            </div>
          </div>

          <div>
            <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
            <ul class="space-y-2">
              <li><a href="index.html" class="text-gray-400 hover:text-white">Home</a></li>
              <li><a href="#features" class="text-gray-400 hover:text-white">About Us</a></li>
              <li><a href="#services" class="text-gray-400 hover:text-white">Services</a></li>
              <li><a href="contact.php" class="text-gray-400 hover:text-white">Contact</a></li>
              <li><a href="privacy.php" class="text-gray-400 hover:text-white">Privacy Policy</a></li>
            </ul>
          </div>

          <div id="services">
            <h3 class="text-lg font-semibold mb-4">Our Services</h3>
            <ul class="space-y-2">
              <li><a href="doctors.php" class="text-gray-400 hover:text-white">Doctor Management</a></li>
              <li><a href="patients.php" class="text-gray-400 hover:text-white">Patient Records</a></li>
              <li><a href="appointments.php" class="text-gray-400 hover:text-white">Appointments</a></li>
              <li><a href="prescriptions.php" class="text-gray-400 hover:text-white">Prescriptions</a></li>
              <li><a href="#" class="text-gray-400 hover:text-white">Billing (Coming Soon)</a></li>
            </ul>
          </div>

          <div>
            <h3 class="text-lg font-semibold mb-4">Contact Us</h3>
            <address class="text-gray-400 not-italic">
              <p class="mb-1"><i class="fas fa-map-marker-alt mr-2"></i>123 Medical Drive</p>
              <p class="mb-1">Healthcare City, HC 12345</p>
              <p class="mb-1"><i class="fas fa-phone mr-2"></i>Phone: (123) 456-7890</p>
              <p class="mb-1"><i class="fas fa-envelope mr-2"></i>Email: info@hospital.com</p>
            </address>
          </div>
        </div>

        <div class="border-t border-gray-700 pt-6">
          <p class="text-gray-400 text-center text-sm">
            &copy; <?php echo date("Y"); ?> Hospital Management System. All rights reserved.
          </p>
        </div>
      </div>
    </footer>

    <script>
      // Navbar Configuration
      const navbarConfig = {
        logo: {
          text: "Hospital", // Text next to the logo icon
          icon: "fas fa-hospital" // Font Awesome icon class
        },
        menuItems: [
          // These links are for general site navigation from the landing page.
          // The target pages (e.g., patients.php) will handle authentication.
          { text: "Home", icon: "fas fa-home", href: "index.html" },
          { text: "Doctors", icon: "fas fa-user-md", href: "doctors.php" }, // Link to general doctors info or list
          { text: "Patients", icon: "fas fa-procedures", href: "patients.php" },
          { text: "Nurses", icon: "fas fa-user-nurse", href: "nurse_profile.php" }, // nurse_profile.php often lists nurses for admin
          { text: "Appointments", icon: "fas fa-calendar-check", href: "appointments.php" },
          { text: "Prescriptions", icon: "fas fa-file-prescription", href: "prescriptions.php" }
        ],
        searchPlaceholder: "Search..." // Placeholder for the search input
      };

      // Initialize Navbar
      document.addEventListener("DOMContentLoaded", function () {
        const logoElement = document.querySelector("#navbar .flex-shrink-0");
        if (logoElement) {
          logoElement.innerHTML = `
            <i class="${navbarConfig.logo.icon} text-blue-500 text-2xl mr-2"></i>
            <span class="text-xl font-bold text-gray-800">${navbarConfig.logo.text}</span>
          `;
        }

        const searchInput = document.getElementById("searchInput");
        if (searchInput) {
          searchInput.placeholder = navbarConfig.searchPlaceholder;
        }

        const navMenu = document.getElementById("navMenu");
        const mobileNavMenu = document.getElementById("mobileNavMenu");

        function createMenuItem(item, isMobile) {
            const menuItem = document.createElement("a");
            menuItem.href = item.href;
            if (isMobile) {
                menuItem.className = "block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition duration-300";
            } else {
                menuItem.className = "inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition duration-300";
            }
            menuItem.innerHTML = `<i class="${item.icon} mr-2"></i>${item.text}`;
            return menuItem;
        }

        if (navMenu) {
          navbarConfig.menuItems.forEach((item) => {
            navMenu.appendChild(createMenuItem(item, false));
          });
        }

        if (mobileNavMenu) {
          navbarConfig.menuItems.forEach((item) => {
            mobileNavMenu.appendChild(createMenuItem(item, true));
          });
        }

        const mobileMenuButton = document.getElementById("mobileMenuButton");
        const mobileMenu = document.getElementById("mobileMenu");
        const hamburgerIcon = '<i class="fas fa-bars"></i>';
        const closeIcon = '<i class="fas fa-times"></i>';


        if (mobileMenuButton && mobileMenu) {
          mobileMenuButton.addEventListener("click", function () {
            const isExpanded = mobileMenuButton.getAttribute("aria-expanded") === "true";
            mobileMenuButton.setAttribute("aria-expanded", !isExpanded);
            mobileMenuButton.innerHTML = !isExpanded ? closeIcon : hamburgerIcon;
            mobileMenu.classList.toggle("hidden");
          });
        }

        if (searchInput) {
          searchInput.addEventListener("keypress", function (e) {
            if (e.key === "Enter" && this.value.trim() !== "") {
              // Basic alert, replace with actual search functionality
              alert(`Searching for: ${this.value}`);
              // Example: window.location.href = `/search?q=${encodeURIComponent(this.value)}`;
            }
          });
        }
      });
    </script>
  </body>
</html>
