<?php
    // total page count calculation
    $pages = ((int) ceil($total / $rpp));

    $disabled_params = 'tabindex="-1" aria-disabled="true"';
    // if there are pages to be shown
    if (($pages > 1 || $alwaysShowPagination === true) && $current <= $pages) {
?>
<ul class='<?= implode(' ', $classes['ul']) ?>'>
<?php
        /**
         * Previous Link
         */

        // anchor classes and target
        //$classes = array('copy', 'previous');
        $previous_classes = $classes['li'];
        $previous_params = null;
        $params = $get;
        if (!empty($key)) {
            $params[$key] = ($current - 1);
            $href = ($target) . '?' . http_build_query($params);
        } else {
            $href = ($target) . ($current - 1) . (!empty($params) ? '?' . http_build_query($params) : null);
        }
        $href = preg_replace(
            array('/=$/', '/=&/'),
            array('', '&'),
            $href
        );
        if ($current === 1) {
            $href = '#';
            array_push($previous_classes, 'disabled');
            $previous_params = $disabled_params;
        }
?>
    <li class='<?= implode(' ', $previous_classes) ?>' alt='<?= ($previous_label) ?>' title='<?= ($previous_label) ?>'><a class='<?= implode(' ', $classes['a']) ?>' href='<?= ($href) ?>'<?= ($previous_params) ?>><?= ($previous) ?></a></li>
<?php
        /**
         * if this isn't a clean output for pagination (eg. show numerical
         * links)
         */
        if ($clean === false) {

            /**
             * Calculates the number of leading page crumbs based on the minimum
             *     and maximum possible leading pages.
             */
            $max = min($pages, $crumbs);
            $limit = ((int) floor($max / 2));
            $leading = $limit;
            for ($x = 0; $x < $limit; ++$x) {
                if ($current === ($x + 1)) {
                    $leading = $x;
                    break;
                }
            }
            for ($x = $pages - $limit; $x < $pages; ++$x) {
                if ($current === ($x + 1)) {#
                    $leading = $max - ($pages - $x);
                    break;
                }
            }

            // calculate trailing crumb count based on inverse of leading
            $trailing = $max - $leading - 1;

            // generate/render leading crumbs
            for ($x = 0; $x < $leading; ++$x) {

                // class/href setup
                $params = $get;
                if (!empty($key)) {
                    $params[$key] = ($current + $x - $leading);
                    $href = ($target) . '?' . http_build_query($params);
                } else {
                    $href = ($target) . ($current + $x - $leading) . (!empty($params) ? '?' . http_build_query($params) : null);
                }
                $href = preg_replace(
                    array('/=$/', '/=&/'),
                    array('', '&'),
                    $href
                );
?>
    <li class='<?= implode(' ', $classes['li']) ?>'><a class='<?= implode(' ', $classes['a']) ?>' data-pagenumber='<?= ($current + $x - $leading) ?>' href='<?= ($href) ?>'><?= ($current + $x - $leading) ?></a></li>
<?php
            }

            // print current page
?>
    <li class='<?= implode(' ', $classes['li']) ?> active'><a class='<?= implode(' ', $classes['a']) ?>' data-pagenumber='<?= ($current) ?>' href=''><?= ($current) ?></a></li>
<?php
            // generate/render trailing crumbs
            for ($x = 0; $x < $trailing; ++$x) {

                // class/href setup
                $params = $get;
                if (!empty($key)) {
                    $params[$key] = ($current + $x + 1);
                    $href = ($target) . '?' . http_build_query($params);
                } else {
                    $href = ($target) . ($current + $x + 1) . (!empty($params) ? '?' . http_build_query($params) : null);
                }
                $href = preg_replace(
                    array('/=$/', '/=&/'),
                    array('', '&'),
                    $href
                );
?>
    <li class='<?= implode(' ', $classes['li']) ?>'><a class='<?= implode(' ', $classes['a']) ?>' data-pagenumber='<?= ($current + $x + 1) ?>' href='<?= ($href) ?>'><?= ($current + $x + 1) ?></a></li>
<?php
            }
        }

        /**
         * Next Link
         */

        // anchor classes and target
        //$classes = array('copy', 'next');
        $next_classes = $classes['li'];
        $next_params = null;
        $params = $get;
        if (!empty($key)) {
            $params[$key] = ($current + 1);
            $href = ($target) . '?' . http_build_query($params);
        } else {
            $href = ($target) . ($current + 1) . (!empty($params) ? '?' . http_build_query($params) : null);
        }
        $href = preg_replace(
            array('/=$/', '/=&/'),
            array('', '&'),
            $href
        );
        if ($current === $pages) {
            $href = '#';
            array_push($next_classes, 'disabled');
            $next_params = $disabled_params;
        }
?>
    <li class='<?= implode(' ', $next_classes) ?>' alt='<?= ($next_label) ?>' title='<?= ($next_label) ?>'><a class='<?= implode(' ', $classes['a']) ?>' href='<?= ($href) ?>'<?= ($next_params) ?>><?= ($next) ?></a></li>
</ul>
<?php
    }