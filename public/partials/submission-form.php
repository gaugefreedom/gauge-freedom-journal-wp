<?php
/**
 * Manuscript Submission Form
 */

if (!is_user_logged_in()) {
    ?>
    <div class="gfj-login-required">
        <h2>Login Required</h2>
        <p>You must be logged in to submit manuscripts.</p>
        <div style="margin-top: 20px;">
            <a href="<?php echo wp_login_url(home_url('/submit-manuscript/')); ?>" class="button button-primary button-large">
                Login
            </a>
            <a href="<?php echo wp_registration_url(); ?>" class="button button-large">
                Register as Author
            </a>
        </div>
    </div>
    <?php
    return;
}

if (!current_user_can('submit_manuscripts')) {
    echo '<p>You must have author privileges to submit manuscripts. Please contact the editorial team.</p>';
    return;
}
?>

<div class="gfj-submission-form-container">
    <h1>Submit Manuscript</h1>
    
    <form id="gfj-submission-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('gfj_submit_manuscript', 'gfj_submit_nonce'); ?>
        
        <!-- Article Type -->
        <div class="form-section">
            <h3>1. Article Type</h3>
            <select name="article_type" required>
                <option value="">-- Select Type --</option>
                <option value="research">Research Article (6-12k words)</option>
                <option value="short">Short Communication (2-4k words)</option>
                <option value="protocol">Registered Protocol</option>
                <option value="perspective">Perspective/Tutorial</option>
                <option value="reproducibility">Reproducibility Report</option>
                <option value="dataset">Dataset/Software Note</option>
            </select>
        </div>
        
        <!-- Manuscript Details -->
        <div class="form-section">
            <h3>2. Manuscript Details</h3>
            
            <label for="title">Title *</label>
            <input type="text" name="title" id="title" required class="large-text">
            
            <label for="abstract">Abstract * (visible during triage)</label>
            <textarea name="abstract" id="abstract" rows="8" required class="large-text"></textarea>
            
            <label for="keywords">Keywords * (comma-separated)</label>
            <input type="text" name="keywords" id="keywords" required class="large-text" 
                   placeholder="gauge theory, information geometry, AI safety">
        </div>
        
        <!-- File Uploads -->
        <div class="form-section">
            <h3>3. File Uploads</h3>
            
            <div class="file-upload-notice">
                <strong>⚠️ Important:</strong> You must submit two versions of your manuscript:
                <ul>
                    <li><strong>Blinded Version:</strong> Remove all author names, affiliations, and identifying information (for reviewers)</li>
                    <li><strong>Full Version:</strong> Complete manuscript with all author information (for editors after approval)</li>
                </ul>
            </div>
            
            <label for="blinded_file">Blinded Manuscript (PDF) *</label>
            <input type="file" name="blinded_file" id="blinded_file" accept=".pdf" required>
            <p class="description">For reviewers - no author identifying information</p>
            
            <label for="full_file">Full Manuscript (PDF) *</label>
            <input type="file" name="full_file" id="full_file" accept=".pdf" required>
            <p class="description">Complete manuscript - locked until triage approval</p>
            
            <label for="latex_sources">LaTeX Source Files (ZIP) *</label>
            <input type="file" name="latex_sources" id="latex_sources" accept=".zip" required>
            
            <label for="car_file">CAR File (JSON)</label>
            <input type="file" name="car_file" id="car_file" accept=".json,.car">
            <p class="description">Content-Addressable Receipt for computational reproducibility</p>
        </div>
        
        <!-- Repositories -->
        <div class="form-section">
            <h3>4. Code & Data</h3>
            
            <label for="code_repo">Code Repository URL</label>
            <input type="url" name="code_repo" id="code_repo" class="large-text" 
                   placeholder="https://github.com/...">
            
            <label for="data_repo">Data Repository URL</label>
            <input type="url" name="data_repo" id="data_repo" class="large-text" 
                   placeholder="https://zenodo.org/...">
        </div>
        
        <!-- Statements -->
        <div class="form-section">
            <h3>5. Required Statements</h3>
            
            <label for="ai_statement">AI Contributions Statement *</label>
            <textarea name="ai_statement" id="ai_statement" rows="6" required class="large-text" 
                      placeholder="Describe AI tools used, their roles, human oversight, and known limitations..."></textarea>
            
            <label for="conflicts">Conflicts of Interest *</label>
            <textarea name="conflicts" id="conflicts" rows="4" required class="large-text" 
                      placeholder="Declare any funding, affiliations, or conflicts of interest, or state 'None'"></textarea>
            
            <label for="cover_letter">Cover Letter</label>
            <textarea name="cover_letter" id="cover_letter" rows="6" class="large-text" 
                      placeholder="Brief cover letter explaining significance and fit with journal scope..."></textarea>
        </div>
        
        <!-- Submit -->
        <div class="form-section">
            <p class="submit-notice">
                <strong>By submitting, you confirm that:</strong>
            </p>
            <ul>
                <li>This work is original and not under consideration elsewhere</li>
                <li>All authors have approved the submission</li>
                <li>You agree to the journal's open science policies</li>
                <li>You will make code and data publicly available upon acceptance</li>
            </ul>
            
            <button type="submit" class="button button-primary button-large">
                Submit Manuscript
            </button>
            <a href="<?php echo home_url('/dashboard/'); ?>" class="button button-large">Cancel</a>
        </div>
    </form>
</div>