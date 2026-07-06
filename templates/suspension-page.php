<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$logo_url = IU_PLUGIN_URL . 'assets/images/logo.png';

// Locale detection with WPML support for suspension page
$current_locale = get_locale();

// Check for WPML current language
if (function_exists('icl_get_current_language')) {
    $current_lang = icl_get_current_language();
    $is_greek = ($current_lang === 'el');
} else {
    // Fallback to WordPress locale
    $is_greek = (strpos($current_locale, 'el') === 0);
}

// Set texts based on site language
if ($is_greek) {
    $title = 'Ιστοσελίδα προσωρινά μη διαθέσιμη';
    $message1 = 'Η λειτουργία της ιστοσελίδας έχει προσωρινά ανασταλεί για λόγους που σχετίζονται με τη φιλοξενία ή τη συντήρησή της.';
    $message2 = 'Εάν είστε ο ιδιοκτήτης του ' . esc_html($_SERVER['HTTP_HOST']) . ', παρακαλούμε επικοινωνήστε με την ομάδα υποστήριξης της ISTODATA για περισσότερες πληροφορίες.';
    $phone_label = 'Τηλ:';
} else {
    $title = 'Website Temporarily Unavailable';
    $message1 = 'The website has been temporarily suspended for reasons related to hosting or maintenance.';
    $message2 = 'If you are the owner of ' . esc_html($_SERVER['HTTP_HOST']) . ', please contact ISTODATA support team for more information.';
    $phone_label = 'Phone:';
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo esc_html($title); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #000;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }      
        .suspension-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            margin: 20px;
        }
        @media only screen and (max-width: 600px) {
			.suspension-container {
				padding: 40px 20px 30px 20px;
			}
		}
        .logo {
            max-width: 200px;
            height: auto;
            margin: 15px;
        }
        h1 {
            color: #e74c3c;
            font-size: 23px;
            margin: 15px 0 22px 0;
            font-weight: 600;
        }
        h2 {
            color: #000;
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 15px;
        }
        p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 15px;
            color: #000;
        }
        .contact-info {
            background: #f8f9fa;
            padding: 10px 0 15px 0;
            border-radius: 8px;
            margin-top: 30px;
        }
        .contact-info a {
            color: #56cb89;
            font-weight: 500;
            text-decoration: none;
        }
        .contact-info p {
            margin: 5px 0;
		}
        .contact-info a:hover {
            text-decoration: underline;
		}
    </style>
</head>
<body>
    <div class="suspension-container">
        <a target="_blank" href="https://www.istodata.com/">
			<img src="<?php echo esc_url($logo_url); ?>" alt="ISTODATA" class="logo">
		</a>
        <h1><?php echo esc_html($title); ?></h1>
        <p><?php echo esc_html($message1); ?></p>
        <p><?php echo $message2; ?></p>
        <div class="contact-info">
            <h2>ISTODATA Helpdesk</h2></p>
            <p><?php echo esc_html($phone_label); ?> <a href="tel:00302111989240">(+30) 211 19 89 240</a></p>
            <p>Email: <a href="#" id="email-link"></a></p>
        </div>
    </div>
    <script>
        (function() {
            var user = 'helpdesk';
            var domain = 'istodata.com';
            var email = user + '@' + domain;
            var link = document.getElementById('email-link');
            link.href = 'mailto:' + email;
            link.textContent = email;
        })();
    </script>
</body>
</html>
