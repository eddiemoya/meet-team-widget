<div class="member clearfix">
    <?php get_partial( 'parts/crest', array( "user_id" => $user->ID, "width" => 'span4', "titling" => true, "show_name" => false, "show_address" => false ) ); ?>
    <article class="info span8">
        <h4><?php get_screenname_link( $user->ID ); ?></h4>
        <address><?php echo $user->address; ?></address>
        <?php if ( ( $show_specializations === 'on' ) && ( !empty( $user->categories ) ) ): ?>
            <h5>Specializes in</h5>
            <ul>
                <?php foreach ($user->categories as $category): ?>
                    <li><a href="<?php echo get_category_link( $category->term_id ); ?>"><?php echo $category->name; ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
    <div class="stats">
        <?php if ( $user->most_recent_post_date ): ?>
        <p>Last posted on <time datetime="<?php echo $user->pubdate; ?>" pubdate="pubdate"><?php echo $user->most_recent_post_date; ?></time>.</p>
        <?php endif; ?>
        <?php if ( $show_response_stats === 'on' ): ?>
            <ul>
                <li><?php echo $user->answer_count . ' ' . _n( 'answer', 'answers', $user->answer_count ); ?></li>
                <li><?php echo $user->post_count . ' ' . _n( 'post', 'posts', $user->post_count ); ?></li>
                <li><?php echo $user->comment_count . ' ' . _n( 'comment', 'comments', $user->comment_count ); ?></li>
            </ul>
        <?php endif; ?>
    </div>
</div>