<?php do_hook('init'); ?>

<!DOCTYPE html>
<?php do_hook('before_html');?>
<html>
<head>
    <?php do_hook('head_start'); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <?php do_hook('enqueue_scripts'); ?>
    <?php do_hook('head_end'); ?>
</head>
<body>
    <?php do_hook('body_start'); ?>
    
    <?php do_hook('before_header'); ?>
    
    <?php do_hook('header'); ?>
    
    <?php do_hook('after_header'); ?>


    <?php do_hook('before_footer'); ?>
    
    <?php do_hook('footer'); ?>
    
    <?php do_hook('after_footer'); ?>

    <?php do_hook('body_end'); ?>
</body>
</html>