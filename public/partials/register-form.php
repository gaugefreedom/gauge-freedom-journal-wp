<?php
/**
 * Custom Registration Form for Authors and Reviewers
 */

if (is_user_logged_in()) {
    wp_safe_redirect(home_url('/dashboard/'));
    exit;
}

$registration_enabled = get_option('users_can_register');
if (!$registration_enabled) {
    echo '<p>Registration is currently disabled. Please contact the journal administrator.</p>';
    return;
}

$errors = [];
$success = false;

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gfj_register_nonce'])) {
    if (!wp_verify_nonce($_POST['gfj_register_nonce'], 'gfj_register')) {
        $errors[] = 'Security verification failed.';
    } else {
        // Validate input
        $username = sanitize_user($_POST['username'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $role = sanitize_text_field($_POST['role'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Honeypot spam protection (bots fill this, humans don't)
        $honeypot = $_POST['website'] ?? '';
        if (!empty($honeypot)) {
            // This is likely spam, silently fail
            $errors[] = 'Registration failed. Please try again.';
        }

        // Validation
        if (empty($username) || strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters long.';
        }

        if (username_exists($username)) {
            $errors[] = 'Username already exists.';
        }

        if (!is_email($email)) {
            $errors[] = 'Invalid email address.';
        }

        if (email_exists($email)) {
            $errors[] = 'Email already registered.';
        }

        if (empty($first_name) || empty($last_name)) {
            $errors[] = 'First and last name are required.';
        }

        if (!in_array($role, ['gfj_author', 'gfj_reviewer'])) {
            $errors[] = 'Invalid role selected.';
        }

        if (empty($password) || strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }

        // Create user if no errors
        if (empty($errors)) {
            $user_id = wp_create_user($username, $password, $email);

            if (is_wp_error($user_id)) {
                $errors[] = $user_id->get_error_message();
            } else {
                // Update user data
                wp_update_user([
                    'ID' => $user_id,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'display_name' => $first_name . ' ' . $last_name,
                    'role' => $role,
                ]);

                $success = true;

                // Send notification to admin
                $admin_email = get_option('admin_email');
                $subject = '[GFJ] New User Registration: ' . $username;
                $message = "A new user has registered:\n\n";
                $message .= "Username: {$username}\n";
                $message .= "Email: {$email}\n";
                $message .= "Name: {$first_name} {$last_name}\n";
                $message .= "Role: " . str_replace('gfj_', '', $role) . "\n\n";
                $message .= "View user: " . admin_url('user-edit.php?user_id=' . $user_id) . "\n";
                wp_mail($admin_email, $subject, $message);

                // Auto-login
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);

                // Redirect to dashboard
                wp_safe_redirect(home_url('/dashboard/'));
                exit;
            }
        }
    }
}
?>

<div class="gfj-register-container" style="max-width: 600px; margin: 40px auto; padding: 20px;">
    <h1>Register for Gauge Freedom Journal</h1>

    <?php if ($success): ?>
        <div class="gfj-message gfj-success">
            ✅ Registration successful! Redirecting to dashboard...
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="gfj-message gfj-error">
            <strong>Please fix the following errors:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" id="gfj-register-form" class="gfj-form" style="background: #fff; padding: 30px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <?php wp_nonce_field('gfj_register', 'gfj_register_nonce'); ?>

        <div class="form-section">
            <h3>Account Information</h3>

            <label for="username">Username *</label>
            <input type="text" name="username" id="username" required
                   value="<?php echo esc_attr($_POST['username'] ?? ''); ?>"
                   class="large-text" minlength="3">
            <p class="description">At least 3 characters, letters and numbers only</p>

            <label for="email">Email Address *</label>
            <input type="email" name="email" id="email" required
                   value="<?php echo esc_attr($_POST['email'] ?? ''); ?>"
                   class="large-text">
        </div>

        <div class="form-section">
            <h3>Personal Information</h3>

            <label for="first_name">First Name *</label>
            <input type="text" name="first_name" id="first_name" required
                   value="<?php echo esc_attr($_POST['first_name'] ?? ''); ?>"
                   class="large-text">

            <label for="last_name">Last Name *</label>
            <input type="text" name="last_name" id="last_name" required
                   value="<?php echo esc_attr($_POST['last_name'] ?? ''); ?>"
                   class="large-text">
        </div>

        <div class="form-section">
            <h3>Role Selection</h3>

            <p style="margin-bottom: 15px; color: #4b5563;">
                <strong>Choose your role:</strong>
            </p>

            <label style="display: block; padding: 15px; border: 2px solid #e5e7eb; border-radius: 6px; margin-bottom: 10px; cursor: pointer;">
                <input type="radio" name="role" value="gfj_author" required
                       <?php checked($_POST['role'] ?? '', 'gfj_author'); ?>>
                <strong>Author</strong> - Submit manuscripts for review
                <p style="margin: 5px 0 0 25px; font-size: 14px; color: #6b7280;">
                    I want to submit my research for publication
                </p>
            </label>

            <label style="display: block; padding: 15px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;">
                <input type="radio" name="role" value="gfj_reviewer" required
                       <?php checked($_POST['role'] ?? '', 'gfj_reviewer'); ?>>
                <strong>Reviewer</strong> - Review submitted manuscripts
                <p style="margin: 5px 0 0 25px; font-size: 14px; color: #6b7280;">
                    I want to peer review submissions
                </p>
            </label>

            <p class="description" style="margin-top: 15px;">
                <strong>Note:</strong> Editor roles are by invitation only. Contact the editorial team if interested.
            </p>
        </div>

        <div class="form-section">
            <h3>Password</h3>

            <label for="password">Password *</label>
            <input type="password" name="password" id="password" required
                   class="large-text" minlength="8">
            <p class="description">At least 8 characters</p>

            <label for="confirm_password">Confirm Password *</label>
            <input type="password" name="confirm_password" id="confirm_password" required
                   class="large-text" minlength="8">
        </div>

        <!-- Honeypot field - hidden from humans, bots will fill it -->
        <div style="position: absolute; left: -5000px;" aria-hidden="true">
            <label for="website">Website (leave blank)</label>
            <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
        </div>

        <div class="form-section">
            <button type="submit" class="button button-primary button-large">
                Create Account
            </button>

            <p style="margin-top: 20px;">
                Already have an account?
                <a href="<?php echo wp_login_url(home_url('/dashboard/')); ?>">Log in here</a>
            </p>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Password strength indicator (simple)
    $('#password').on('input', function() {
        var password = $(this).val();
        var strength = 0;

        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;

        var $indicator = $('.password-strength');
        if (!$indicator.length) {
            $(this).after('<div class="password-strength" style="margin-top: 5px; font-size: 13px;"></div>');
            $indicator = $('.password-strength');
        }

        if (strength <= 2) {
            $indicator.html('Weak password').css('color', '#dc2626');
        } else if (strength <= 3) {
            $indicator.html('Medium strength').css('color', '#d97706');
        } else {
            $indicator.html('Strong password').css('color', '#059669');
        }
    });

    // Real-time password match check
    $('#confirm_password').on('input', function() {
        var password = $('#password').val();
        var confirm = $(this).val();

        var $match = $('.password-match');
        if (!$match.length) {
            $(this).after('<div class="password-match" style="margin-top: 5px; font-size: 13px;"></div>');
            $match = $('.password-match');
        }

        if (confirm.length > 0) {
            if (password === confirm) {
                $match.html('✓ Passwords match').css('color', '#059669');
            } else {
                $match.html('✗ Passwords do not match').css('color', '#dc2626');
            }
        } else {
            $match.html('');
        }
    });
});
</script>
