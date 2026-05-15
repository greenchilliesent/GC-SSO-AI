<?php
/**
 * Chillies Page Builder — Blog Template
 *
 * Use via shortcode: [chillies_page_builder template="blog"]
 */
$paged = get_query_var( 'paged' ) ?: 1;
$query = new WP_Query( [ 'post_type' => 'post', 'posts_per_page' => 9, 'paged' => $paged ] );
?>
<style>
.cpt-blog { font-family: 'Inter', system-ui, sans-serif; }
.cpt-blog-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px,1fr)); gap: 24px; padding: 32px 0; }
.cpt-post-card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; overflow: hidden; transition: border-color .2s, transform .2s; }
.cpt-post-card:hover { border-color: #6366f1; transform: translateY(-2px); }
.cpt-post-thumb img { width: 100%; height: 200px; object-fit: cover; display: block; }
.cpt-post-thumb-placeholder { width: 100%; height: 200px; background: linear-gradient(135deg,#1e1b4b,#312e81); display: flex; align-items: center; justify-content: center; font-size: 3rem; }
.cpt-post-body { padding: 20px; }
.cpt-post-meta { font-size: 12px; color: #94a3b8; margin-bottom: 8px; }
.cpt-post-title { font-size: 1.05rem; font-weight: 700; color: #e2e8f0; margin: 0 0 10px; }
.cpt-post-title a { text-decoration: none; color: inherit; }
.cpt-post-title a:hover { color: #6366f1; }
.cpt-post-excerpt { font-size: .875rem; color: #94a3b8; margin: 0 0 16px; }
.cpt-read-more { display: inline-block; font-size: .8rem; font-weight: 600; color: #6366f1; text-decoration: none; }
.cpt-read-more:hover { color: #818cf8; }
.cpt-pagination { text-align: center; padding: 24px 0; }
.cpt-pagination a, .cpt-pagination span { display: inline-block; padding: 7px 14px; margin: 0 3px; border-radius: 6px; border: 1px solid #334155; color: #e2e8f0; text-decoration: none; font-size: .875rem; }
.cpt-pagination span.current { background: #6366f1; border-color: #6366f1; color: #fff; }
.cpt-pagination a:hover { border-color: #6366f1; color: #6366f1; }
</style>

<div class="cpt-blog">
    <?php if ( $query->have_posts() ) : ?>
    <div class="cpt-blog-grid">
        <?php while ( $query->have_posts() ) : $query->the_post(); ?>
        <article class="cpt-post-card">
            <?php if ( has_post_thumbnail() ) : ?>
            <div class="cpt-post-thumb"><?php the_post_thumbnail( 'medium_large' ); ?></div>
            <?php else : ?>
            <div class="cpt-post-thumb-placeholder">&#128196;</div>
            <?php endif; ?>
            <div class="cpt-post-body">
                <div class="cpt-post-meta"><?php echo get_the_date(); ?> &middot; <?php the_author(); ?></div>
                <h2 class="cpt-post-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <p class="cpt-post-excerpt"><?php echo wp_trim_words( get_the_excerpt(), 18 ); ?></p>
                <a class="cpt-read-more" href="<?php the_permalink(); ?>">Read more &rarr;</a>
            </div>
        </article>
        <?php endwhile; wp_reset_postdata(); ?>
    </div>
    <div class="cpt-pagination">
        <?php
        echo paginate_links( [
            'total'   => $query->max_num_pages,
            'current' => $paged,
        ] );
        ?>
    </div>
    <?php else : ?>
    <p style="text-align:center;padding:60px;color:#94a3b8;">No posts found.</p>
    <?php endif; ?>
</div>
