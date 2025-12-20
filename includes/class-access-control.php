<?php
/**
 * Access Control Class
 * 
 * Handles server-side authentication for portal pages.
 * 
 * HOW THIS WORKS:
 * ===============
 * Instead of redirecting to wp-login.php, we display a branded login form
 * directly on the portal page. But here's the critical difference from
 * JavaScript-based gates:
 * 
 * JavaScript gate (BAD):
 *   1. Page loads with ALL content (protected stuff included)
 *   2. JavaScript checks if logged in
 *   3. If not, CSS hides the content
 *   4. Problem: Disable JS, see everything
 * 
 * Server-side gate (THIS APPROACH):
 *   1. PHP checks if logged in BEFORE generating any output
 *   2. If not logged in: generate ONLY the login form, stop there
 *   3. Protected content is never generated, never sent, never in the HTML
 *   4. No bypass possible - the content literally doesn't exist for unauthorized users
 *
 * @package BBAB_Portal
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BBAB_Portal_Access_Control {

    /**
     * Constructor - hooks into WordPress
     */
    public function __construct() {
        // template_redirect fires BEFORE any output - perfect for access control
        add_action( 'template_redirect', array( $this, 'check_access' ) );
        
        // Handle failed login redirects back to portal
        add_action( 'wp_login_failed', array( $this, 'handle_failed_login' ) );
    }

    /**
     * Check if current user can access portal pages
     * 
     * If not authorized, we take over the page output entirely
     * and show only the login form.
     */
    public function check_access() {
        // Skip admin pages - we only protect frontend
        if ( is_admin() ) {
            return;
        }

        // Check if this is a portal page
        if ( ! $this->is_protected_page() ) {
            return;
        }

        // User is logged in AND is an administrator? Let them through
        if ( is_user_logged_in() && current_user_can( 'administrator' ) ) {
            return;
        }

        // NOT AUTHORIZED - Take over the page completely
        // We output our own page with just the login form, then exit
        // The theme's normal content NEVER gets generated
        $this->render_login_page();
        exit;
    }

    /**
     * Render a complete login page
     * 
     * This outputs a full HTML page with the branded login form.
     * Because we call exit after this, the theme template never runs
     * and the protected content is never generated.
     */
    private function render_login_page() {
        // Check for login errors
        $error_message = '';
        if ( isset( $_GET['login'] ) && $_GET['login'] === 'failed' ) {
            $error_message = '<div class="bbab-login-error">Invalid username or password. Please try again.</div>';
        }

        // Get the current page URL for redirect after login
        $redirect_url = get_permalink();
        
        // Start output
        ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php bloginfo( 'name' ); ?></title>
    <?php wp_head(); ?>
    <style>
        /* Reset and base */
        * {
            box-sizing: border-box;
        }
        
        body.bbab-login-page {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #F3F5F8;
            font-family: 'Poppins', sans-serif;
        }

        .bbab-login-container {
            max-width: 450px;
            width: 100%;
            margin: 20px;
            padding: 40px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .bbab-login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .bbab-login-logo {
            max-width: 80px;
            height: auto;
            margin-bottom: 15px;
        }

        .bbab-login-title {
            font-size: 28px;
            font-weight: 600;
            color: #1C244B;
            margin: 0;
        }

        .bbab-login-subtitle {
            font-size: 16px;
            color: #324A6D;
            margin: 10px 0 0 0;
        }

        .bbab-login-error {
            background-color: #fee;
            border: 1px solid #e74c3c;
            color: #c0392b;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .bbab-login-form label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #1C244B;
            margin-bottom: 6px;
        }

        .bbab-login-form input[type="text"],
        .bbab-login-form input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: border-color 0.3s;
        }

        .bbab-login-form input[type="text"]:focus,
        .bbab-login-form input[type="password"]:focus {
            outline: none;
            border-color: #467FF7;
        }

        .bbab-login-field {
            margin-bottom: 20px;
        }

        .bbab-login-remember {
            margin-bottom: 25px;
        }

        .bbab-login-remember label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 400;
            color: #324A6D;
            cursor: pointer;
        }

        .bbab-login-remember input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .bbab-login-submit {
            width: 100%;
            padding: 14px;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            background-color: #467FF7;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .bbab-login-submit:hover {
            background-color: #3568d4;
        }

        .bbab-login-footer {
            text-align: center;
            margin-top: 20px;
        }

        .bbab-login-footer a {
            font-size: 14px;
            color: #467FF7;
            text-decoration: none;
        }

        .bbab-login-footer a:hover {
            text-decoration: underline;
        }

        /* Hide any WordPress admin bar stuff */
        #wpadminbar {
            display: none !important;
        }
    </style>
</head>
<body class="bbab-login-page">

    <div class="bbab-login-container">
        <div class="bbab-login-header">
            <?php if ( get_site_icon_url() ) : ?>
                <img src="<?php echo esc_url( get_site_icon_url( 150 ) ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" class="bbab-login-logo">
            <?php endif; ?>
            <h1 class="bbab-login-title">Brad's Portal</h1>
            <p class="bbab-login-subtitle">Sign in to access your dashboard</p>
        </div>

        <?php echo $error_message; ?>

        <form method="post" action="<?php echo esc_url( wp_login_url() ); ?>" class="bbab-login-form">
            <div class="bbab-login-field">
                <label for="user_login">Username or Email</label>
                <input type="text" name="log" id="user_login" required>
            </div>

            <div class="bbab-login-field">
                <label for="user_pass">Password</label>
                <input type="password" name="pwd" id="user_pass" required>
            </div>

            <div class="bbab-login-remember">
                <label>
                    <input type="checkbox" name="rememberme" value="forever">
                    Remember me
                </label>
            </div>

            <input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_url ); ?>">

            <button type="submit" name="wp-submit" class="bbab-login-submit">Sign In</button>
        </form>

        <div class="bbab-login-footer">
            <a href="<?php echo esc_url( wp_lostpassword_url( get_permalink() ) ); ?>">Forgot your password?</a>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
        <?php
    }

    /**
     * Handle failed login - redirect back to portal with error
     * 
     * When someone fails to login, WordPress normally shows wp-login.php
     * with an error. We intercept this and send them back to the portal
     * page with an error parameter instead.
     *
     * @param string $username The username that failed
     */
    public function handle_failed_login( $username ) {
        $referrer = wp_get_referer();
        
        // Check if login attempt came from a portal page
        if ( $referrer && strpos( $referrer, 'brads-portal' ) !== false ) {
            // Remove any existing login parameter and add failed
            $redirect_url = remove_query_arg( 'login', $referrer );
            $redirect_url = add_query_arg( 'login', 'failed', $redirect_url );
            
            wp_redirect( $redirect_url );
            exit;
        }
    }

    /**
     * Determine if the current page is a protected portal page
     * 
     * We protect:
     * - The main portal page (/brads-portal/)
     * - Any child pages of the portal (/brads-portal/add-portfolio/, etc.)
     *
     * @return bool True if current page should be protected
     */
    public function is_protected_page() {
        global $post;

        // No post object? Not a page we care about
        if ( ! $post ) {
            return false;
        }

        // Only check pages, not posts or other content types
        if ( $post->post_type !== 'page' ) {
            return false;
        }

        // Check 1: Is this the main portal page?
        if ( $post->post_name === 'brads-portal' ) {
            return true;
        }

        // Check 2: Is this a child of the portal page?
        if ( $post->post_parent ) {
            $parent = get_post( $post->post_parent );
            if ( $parent && $parent->post_name === 'brads-portal' ) {
                return true;
            }
        }

        // Not a portal page
        return false;
    }
}