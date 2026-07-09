<?php
// File: /index.php
require_once __DIR__ . '/config/constants.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name = htmlspecialchars(strip_tags(trim($_POST['name'] ?? '')));
    $company = htmlspecialchars(strip_tags(trim($_POST['company'] ?? '')));
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $phone = htmlspecialchars(strip_tags(trim($_POST['phone'] ?? '')));
    $message = htmlspecialchars(strip_tags(trim($_POST['message'] ?? '')));

    if (!$name || !$email || !$message) {
        $error = "Name, valid Email, and Message are required.";
    } else {
        // Here you would typically send an email or save to DB.
        // Mocking success for the landing page context.
        $success = "Thank you for reaching out! Our team will contact you shortly.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="VEXA is a comprehensive SaaS Digital Marketing Management Software for modern agencies. Streamline projects, clients, and teams all in one place.">

    <!-- Open Graph Tags -->
    <meta property="og:title" content="<?php echo APP_NAME; ?> - Digital Marketing Management Software">
    <meta property="og:description" content="Streamline your digital marketing agency. Manage projects, tasks, clients, and files effortlessly.">
    <meta property="og:image" content="/assets/og-image.jpg">
    <meta property="og:url" content="<?php echo BASE_URL; ?>">
    <meta property="og:type" content="website">

    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo APP_NAME; ?> - Agency Management">
    <meta name="twitter:description" content="Streamline your digital marketing agency with VEXA.">
    <meta name="twitter:image" content="/assets/twitter-image.jpg">

    <title><?php echo APP_NAME; ?> - Digital Marketing Management Software</title>

    <!-- Favicon Placeholder -->
    <link rel="icon" href="/favicon.ico" type="image/x-icon">

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; scroll-behavior: smooth; }
        .hero-pattern { background-image: radial-gradient(#e5e7eb 1px, transparent 1px); background-size: 20px 20px; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 flex flex-col min-h-screen">

    <!-- Navigation -->
    <header class="bg-white/90 backdrop-blur-md shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex-shrink-0 flex items-center">
                    <!-- Logo Placeholder -->
                    <span class="text-3xl font-black text-indigo-600 tracking-tight"><?php echo APP_NAME; ?></span>
                </div>
                <nav class="hidden md:flex space-x-8">
                    <a href="#home" class="text-gray-600 hover:text-indigo-600 font-medium transition">Home</a>
                    <a href="#features" class="text-gray-600 hover:text-indigo-600 font-medium transition">Features</a>
                    <a href="#pricing" class="text-gray-600 hover:text-indigo-600 font-medium transition">Pricing</a>
                    <a href="#faq" class="text-gray-600 hover:text-indigo-600 font-medium transition">FAQ</a>
                    <a href="#contact" class="text-gray-600 hover:text-indigo-600 font-medium transition">Contact</a>
                </nav>
                <div class="flex items-center space-x-4">
                    <a href="/login.php" class="text-gray-600 hover:text-indigo-600 font-medium transition hidden sm:block">Login</a>
                    <a href="#contact" class="bg-indigo-600 text-white px-6 py-2.5 rounded-full hover:bg-indigo-700 transition shadow-md font-medium">Request Demo</a>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-grow">
        <!-- Hero Section -->
        <section id="home" class="relative pt-20 pb-32 overflow-hidden hero-pattern">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
                <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight text-gray-900 mb-6">
                    Manage your Digital Agency <br><span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-blue-500">without the chaos.</span>
                </h1>
                <p class="mt-4 max-w-2xl mx-auto text-xl text-gray-600 mb-10">
                    VEXA brings your clients, projects, tasks, file hosting, and team collaboration into one unified, intelligent workspace.
                </p>
                <div class="flex justify-center space-x-4">
                    <a href="#contact" class="bg-indigo-600 text-white px-8 py-4 rounded-full text-lg hover:bg-indigo-700 transition shadow-lg font-bold">Start Free Trial</a>
                    <a href="#features" class="bg-white border-2 border-gray-200 text-gray-700 px-8 py-4 rounded-full text-lg hover:bg-gray-50 transition shadow-sm font-bold">Explore Features</a>
                </div>

                <!-- Screenshots Placeholder -->
                <div class="mt-16 mx-auto max-w-5xl">
                    <div class="rounded-2xl shadow-2xl overflow-hidden border border-gray-200 bg-gray-800 aspect-video flex items-center justify-center relative">
                        <span class="text-gray-400 font-medium text-lg">Application Dashboard Screenshot Placeholder</span>
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900/50 to-transparent"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features / Modules -->
        <section id="features" class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-indigo-600 font-bold tracking-wide uppercase">Complete Workflow</h2>
                    <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">Everything you need to scale</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                    <!-- Feature 1 -->
                    <div class="bg-gray-50 rounded-2xl p-8 hover:shadow-lg transition">
                        <div class="bg-indigo-100 w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Agency Management</h3>
                        <p class="text-gray-600">Multi-tier role management spanning Owners, Managers, CRMs, and specialized Employees.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-gray-50 rounded-2xl p-8 hover:shadow-lg transition">
                        <div class="bg-blue-100 w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">CRM & Task Workflows</h3>
                        <p class="text-gray-600">CRMs manage projects and dispatch tasks. Review work seamlessly with built-in revision tracking.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-gray-50 rounded-2xl p-8 hover:shadow-lg transition">
                        <div class="bg-green-100 w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" /></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Google Drive Sync</h3>
                        <p class="text-gray-600">Connect your agency's Drive. Files upload automatically into structured project folders.</p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="bg-gray-50 rounded-2xl p-8 hover:shadow-lg transition">
                        <div class="bg-purple-100 w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" /></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Voice Notes</h3>
                        <p class="text-gray-600">Leave direct audio feedback on tasks and submissions. Faster than typing, clearer than text.</p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="bg-gray-50 rounded-2xl p-8 hover:shadow-lg transition">
                        <div class="bg-yellow-100 w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Deep Analytics</h3>
                        <p class="text-gray-600">Monitor employee productivity, revenue metrics, and project timelines with rich visual reports.</p>
                    </div>

                    <!-- Feature 6 -->
                    <div class="bg-gray-50 rounded-2xl p-8 hover:shadow-lg transition">
                        <div class="bg-red-100 w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Global Notifications</h3>
                        <p class="text-gray-600">Never miss an update. Stay informed with real-time global notifications and audit logs.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Choose VEXA -->
        <section class="py-20 bg-indigo-900 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl mb-8">Why Choose VEXA?</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
                    <div>
                        <h4 class="text-xl font-bold mb-2">Centralized Hub</h4>
                        <p class="text-indigo-200">Replace 5 different apps with one seamless platform designed specifically for agencies.</p>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold mb-2">Secure & Scalable</h4>
                        <p class="text-indigo-200">Built on modern, lightweight PHP architecture using strict PDO constraints and CSRF protection.</p>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold mb-2">No Hidden Fees</h4>
                        <p class="text-indigo-200">Unlike SaaS tools that charge per gigabyte, we integrate directly with your Google Drive.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Placeholder -->
        <section id="pricing" class="py-24 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Simple, Transparent Pricing</h2>
                    <p class="mt-4 text-xl text-gray-600">Choose the plan that fits your agency.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                    <!-- Basic -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Starter</h3>
                        <p class="text-4xl font-extrabold text-gray-900 mb-6">$49<span class="text-lg font-medium text-gray-500">/mo</span></p>
                        <ul class="text-gray-600 space-y-3 mb-8 text-left">
                            <li>✓ Up to 10 Team Members</li>
                            <li>✓ 50 Active Projects</li>
                            <li>✓ Basic Reporting</li>
                        </ul>
                        <a href="#contact" class="block w-full py-3 px-4 bg-indigo-50 text-indigo-700 font-bold rounded-xl hover:bg-indigo-100 transition">Select Plan</a>
                    </div>
                    <!-- Pro -->
                    <div class="bg-indigo-600 rounded-2xl shadow-xl border border-indigo-700 p-8 text-center transform md:-translate-y-4">
                        <h3 class="text-xl font-bold text-white mb-4">Professional</h3>
                        <p class="text-4xl font-extrabold text-white mb-6">$99<span class="text-lg font-medium text-indigo-200">/mo</span></p>
                        <ul class="text-indigo-100 space-y-3 mb-8 text-left">
                            <li>✓ Unlimited Team Members</li>
                            <li>✓ Unlimited Projects</li>
                            <li>✓ Google Drive Integration</li>
                            <li>✓ Advanced Analytics</li>
                        </ul>
                        <a href="#contact" class="block w-full py-3 px-4 bg-white text-indigo-600 font-bold rounded-xl hover:bg-gray-50 transition shadow-md">Select Plan</a>
                    </div>
                    <!-- Enterprise -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Enterprise</h3>
                        <p class="text-4xl font-extrabold text-gray-900 mb-6">$199<span class="text-lg font-medium text-gray-500">/mo</span></p>
                        <ul class="text-gray-600 space-y-3 mb-8 text-left">
                            <li>✓ White-label Solution</li>
                            <li>✓ Dedicated Account Manager</li>
                            <li>✓ Priority Support</li>
                        </ul>
                        <a href="#contact" class="block w-full py-3 px-4 bg-indigo-50 text-indigo-700 font-bold rounded-xl hover:bg-indigo-100 transition">Select Plan</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section id="faq" class="py-24 bg-white">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-extrabold text-center tracking-tight text-gray-900 sm:text-4xl mb-12">Frequently Asked Questions</h2>
                <div class="space-y-6">
                    <div class="bg-gray-50 p-6 rounded-xl border border-gray-100">
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Can clients log into VEXA?</h4>
                        <p class="text-gray-600">Currently, VEXA is designed for internal agency use to manage work. Client portals will be considered in future roadmaps.</p>
                    </div>
                    <div class="bg-gray-50 p-6 rounded-xl border border-gray-100">
                        <h4 class="text-lg font-bold text-gray-900 mb-2">How does the Google Drive integration work?</h4>
                        <p class="text-gray-600">The Agency Owner connects their central Drive account via OAuth. VEXA will automatically create organized folders and upload employee submissions directly to your Drive.</p>
                    </div>
                    <div class="bg-gray-50 p-6 rounded-xl border border-gray-100">
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Is the platform secure?</h4>
                        <p class="text-gray-600">Yes, VEXA utilizes industry-standard security measures including strict PDO parameters, CSRF tokens on all state-changing endpoints, and robust session validation.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="py-24 bg-gray-900">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Get In Touch</h2>
                    <p class="mt-4 text-lg text-gray-400">Request a demo or ask us anything.</p>
                </div>

                <div class="bg-white rounded-2xl shadow-xl p-8 sm:p-12">
                    <?php if ($success): ?>
                        <div class="bg-green-50 text-green-700 p-4 rounded-lg mb-6 border border-green-200 text-center font-medium">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6 border border-red-200 text-center font-medium">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php#contact" class="space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                                <input type="text" id="name" name="name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                            </div>
                            <div>
                                <label for="company" class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                                <input type="text" id="company" name="company" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                                <input type="email" id="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="text" id="phone" name="phone" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                            </div>
                        </div>
                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                            <textarea id="message" name="message" rows="4" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none"></textarea>
                        </div>
                        <button type="submit" name="contact_submit" class="w-full bg-indigo-600 text-white font-bold py-4 rounded-xl hover:bg-indigo-700 transition shadow-md text-lg">Send Message</button>
                    </form>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 border-t border-gray-800 pt-12 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-6 md:mb-0">
                    <span class="text-2xl font-black text-indigo-500 tracking-tight"><?php echo APP_NAME; ?></span>
                    <p class="text-gray-400 mt-2 text-sm">Empowering Digital Agencies globally.</p>
                </div>
                <div class="flex space-x-6 text-sm text-gray-400">
                    <a href="#" class="hover:text-white transition">Privacy Policy</a>
                    <a href="#" class="hover:text-white transition">Terms of Service</a>
                    <a href="/login.php" class="hover:text-white transition font-medium">Employee Login</a>
                </div>
            </div>
            <div class="mt-8 text-center text-gray-500 text-sm border-t border-gray-800 pt-8">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.
            </div>
        </div>
    </footer>

</body>
</html>
