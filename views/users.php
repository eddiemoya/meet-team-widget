<article class="span12 featured-members content-container">
    <hgroup class="content-header">
        <h3>Meet the Community Team</h3>
        <h4>Whatever your question or issue, we're here to help</h4>
    </hgroup>
    <section class="content-body clearfix">
        <ul class="member-list">
            <?php foreach($users as $user): ?>
                <li class="span4">
                    <?php include "user.php"; ?>
                </li>
            <?php endforeach; ?>
        </li>
    </ul>
    <?php if (!$hide_show_all) : ?>
    
        <span class="see-more clearfix"><a href="<?php echo get_permalink($more_link);?>">See More</a></span>
         
    <?php endif; ?>
</section>
</article>