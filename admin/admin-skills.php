<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

chillies_admin_wrap( 'Skills Manager', function() {
    $skills = Chillies_Skills::get_instance()->get_all();
    ?>
    <div class="chillies-card">
        <h3><span class="dashicons dashicons-plus-alt"></span> Add New Skill</h3>
        <div class="chillies-form-grid">
            <div class="chillies-field-group">
                <label>Name</label>
                <input type="text" id="skill-name" placeholder="e.g. SEO Optimizer">
            </div>
            <div class="chillies-field-group">
                <label>Shortcode</label>
                <input type="text" id="skill-shortcode" placeholder="e.g. chillies_skill_seo">
            </div>
            <div class="chillies-field-group">
                <label>Token Cost</label>
                <input type="text" id="skill-token" placeholder="Unlimited" value="Unlimited">
            </div>
            <div class="chillies-field-group" style="grid-column:span 2;">
                <label>Description</label>
                <textarea id="skill-desc" rows="2" placeholder="What does this skill do?"></textarea>
            </div>
        </div>
        <div style="display:flex;gap:10px;">
            <button class="chillies-btn" id="chillies-add-skill">
                <span class="dashicons dashicons-plus-alt"></span> Add Skill
            </button>
            <button class="chillies-btn chillies-btn-secondary" id="chillies-ai-suggest-skills">
                <span class="dashicons dashicons-superhero-alt"></span> AI Suggest Skills
            </button>
        </div>
    </div>

    <div class="chillies-card" style="margin-top:20px;">
        <h3><span class="dashicons dashicons-list-view"></span> All Skills</h3>
        <table class="chillies-table widefat" id="chillies-skills-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Shortcode</th>
                    <th>Token Cost</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $skills ) : foreach ( $skills as $s ) : ?>
                <tr id="skill-row-<?php echo esc_attr( $s->id ); ?>">
                    <td><?php echo esc_html( $s->name ); ?></td>
                    <td><code>[<?php echo esc_html( $s->shortcode ); ?>]</code></td>
                    <td><?php echo esc_html( $s->token_cost ); ?></td>
                    <td>
                        <span class="chillies-status-badge <?php echo $s->status ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $s->status ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td>
                        <button class="chillies-btn-sm chillies-toggle-skill" data-id="<?php echo esc_attr( $s->id ); ?>">
                            <span class="dashicons dashicons-update"></span> Toggle
                        </button>
                        <button class="chillies-btn-sm chillies-btn-danger chillies-delete-skill" data-id="<?php echo esc_attr( $s->id ); ?>">
                            <span class="dashicons dashicons-trash"></span> Delete
                        </button>
                    </td>
                </tr>
                <?php endforeach; else : ?>
                <tr><td colspan="5">No skills added yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="chillies-ai-suggestions" style="margin-top:20px;display:none;" class="chillies-card">
        <h3><span class="dashicons dashicons-superhero-alt"></span> AI Skill Suggestions</h3>
        <pre id="chillies-ai-suggestions-content" style="white-space:pre-wrap;"></pre>
    </div>
    <?php
});
