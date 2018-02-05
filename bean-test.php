<?php

foreach( get_post_types( '', 'names') as $post_type ) {
    echo '<p>post type: ' . $post_type . '</p>';
}
?>
