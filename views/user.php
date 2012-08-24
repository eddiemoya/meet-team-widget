<div class="member clearfix">
    <?php get_partial( 'parts/crest', array( "user_id" => $user->ID, "width" => '', "titling" => true, "show_name" => false ) ); ?>
    <article class="info">
        <h4><a href="#"><?php echo $user->user_nicename; ?></a></h4>
        <address><?php echo $user->user_city;?>, <?php echo $user->user_state; ?></address>
        <?php if ($show_specializations === 'on') : ?>
            <h5>Specializes in</h5>
            <ul>
                <?php foreach ($user->categories as $category) : ?>
                    <li><a href="<?php echo get_category_link( $category->term_id ); ?>"><?php echo $category->name; ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
    <div class="stats">
        <?php if ( $user->most_recent_post_date ): ?>
        <p>Last posted on <time datetime="<?php echo $user->pubdate; ?>" pubdate="pubdate"><?php echo $user->most_recent_post_date; ?></time>.</p>
        <?php endif; ?>
        <?php if ($show_response_stats === 'on') : ?>
            <ul>
                <?php // echo '<li>' . $user->total_answers . ' ' . _n( 'answer', 'answers', $user->total_answers ) . '</li>'; ?>
                <li><?php echo $user->total_posts . ' ' . _n( 'post', 'posts', $user->total_posts ); ?></li>
                <li><?php echo $user->total_comments . ' ' . _n( 'comment', 'comments', $user->total_comments ); ?></li>
            </ul>
        <?php endif; ?>
    </div>
</div>