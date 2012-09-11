<section class="span12 featured-members content-container">
    <hgroup class="content-header">
        <h3>Meet the Community Team</h3>
        <h4>Whatever your question or issue, we're here to help</h4>
    </hgroup>
    <section class="content-body">
        <?php foreach($users as $user){
            include "user.php";
        } ?>
       <span class="left"><a href="<?php echo get_permalink(get_page_by_title('Experts List'));?>">See More</a></span> 
    </section>
</section>