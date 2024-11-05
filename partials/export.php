<?php

$needed_post_types = get_post_types([
    'public'   => true,
    '_builtin' => false
],'object');
//Append default post types
$needed_post_types['post'] = get_post_type_object( 'post' );
$needed_post_types['page'] = get_post_type_object( 'page' );

?>

<h1> Export Data</h1>

<h3>Select post types and specific posts which are you want to export </h3>

<ul id="available-post-types">
    <?php
    foreach($needed_post_types as $obj) {
        $input_id = 'post_type_'.$obj->name;
        $placeholder = 'Select specific '.$obj->labels->name.' or all ' .$obj->labels->name;
        ?>
        <li class="item-post-type">
            <label for="<?= $input_id?>">
                <input type="checkbox"  id="<?= $input_id?>" class="selected-post-type" name="selected-post-type" value="<?= $obj->name?>" />
                <?= $obj->labels->singular_name?>
            </label>
            <div class="select-posts-wpr">
                <select class="post-ids-list"
                        data-post-type="<?= $obj->name?>"
                        data-placeholder="<?= $placeholder?>"
                        data-post-type="<?= $obj->name?>">
                </select>
                <p><i>If the "All" option was selected, the other options will be ignored.</i></p>
            </div>
        </li>
        <?php
    }
    ?>
</ul>



<p class="submit">
    <input type="submit" name="submit" id="btn-export-data" class="button button-primary" value="Export data">
    <span id="loading-progress" style="display:none;">
        <img src="<?= ITC_DM_URL.'assets/imgs/loading-icon.svg'?>" width="50" alt="" style="position:relative;top:17px;"/>  Exporting data ...
    </span>
</p>