<div class="member clearfix">
    <?php get_partial( 'parts/crest', array( "user_id" => $user->ID, "width" => '', "titling" => true, "show_name" => false ) ); ?>
    <article class="info">
        <h4><a href="#"><?php echo $user->user_nicename; ?></a></h4>
        <address><?php echo $user->user_city;?>, <?php echo $user->user_state; ?></address>
        <?php if ($show_specializations === 'on') : ?>
            <h5>Specializes in</h5>
            <ul>
                <?php foreach ($user->categories as $category) : ?>
                    <li><a href="#"><?php echo $category->name; ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
    <div class="stats">
        <? if ( $user->most_recent_post_date ): ?>
        <p>Last posted on <time datetime="<?php echo $user->pubdate; ?>" pubdate="pubdate"><?php echo $user->most_recent_post_date; ?></time>.</p>
        <?php endif; ?>
        <?php if ($show_response_stats === 'on') : ?>
            <ul>
                <li><?php //echo $user->total_answers; ?>8 answers</li>
                <li><?php echo $user->total_posts; ?> blog post(s)</li>
                <li><?php echo $user->total_comments; ?> comment(s)</li>
            </ul>
        <?php endif; ?>
    </div>
</div>