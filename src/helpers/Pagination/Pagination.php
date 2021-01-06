<?php
namespace helpers\Pagination;

    /**
     * Pagination
     * 
     * Supplies an API for setting pagination details, and renders the resulting
     * pagination markup (html) through the included render.inc.php file.
     * 
     * @note    The SEO methods (canonical/rel) were written following Google's
     *          suggested patterns. Namely, the canoical url excludes any 
     *          peripheral parameters that don't relate to the pagination
     *          series. Whereas the prev/next rel link tags include any params
     *          found in the request.
     * @link    https://github.com/onassar/PHP-Pagination
     * @author  Oliver Nassar <onassar@gmail.com>
     * @todo    add setter parameter type and range checks w/ exceptions
     */
    class Pagination
    {
        /**
         * _variables
         * 
         * Sets default variables for the rendering of the pagination markup.
         * 
         * @var     array
         * @access  protected
         */
        protected $_variables = array(
            'classes' => array(
				'ul' => array('clearfix', 'pagination'),
				'li' => '',
				'a' => ''
			),
            'crumbs' => 5,
            'rpp' => 10,
            'key' => 'page',
            'target' => '',
            'next' => 'Next &raquo;',
            'next_label' => 'Next',
            'previous' => '&laquo; Previous',
            'previous_label' => 'Previous',
            'alwaysShowPagination' => false,
            'clean' => false
        );

        /**
         * __construct
         * 
         * @access  public
         * @param   integer $current (default: null)
         * @param   integer $total (default: null)
         * @return  void
         */
        public function __construct()
        {
            // Pass along get (for link generation)
            $this->_variables['get'] = $_GET;
        }

        /**
         * _check
         * 
         * Checks the current (page) and total (records) parameters to ensure
         * they've been set. Throws an exception otherwise.
         * 
         * @access  protected
         * @return  void
         */
        protected function _check()
        {
            if (isset($this->_variables['current']) === false) {
                throw new Exception('Pagination::current must be set.');
            } elseif (isset($this->_variables['total']) === false) {
                throw new Exception('Pagination::total must be set.');
            }
        }

        /**
         * addClasses
         * 
         * Sets the classes to be added to the pagination div node.
         * Useful with Twitter Bootstrap (eg. pagination-centered, etc.)
         * 
         * @see     http://twitter.github.com/bootstrap/components.html#pagination
         * @access  public
         * @param   mixed $classes
         * @return  void
         */
        public function addClasses($classes)
        {
            $this->_variables['classes'] = array_merge(
                $this->_variables['classes'],
                (array) $classes
            );
        }

        /**
         * alwaysShowPagination
         * 
         * Tells the rendering engine to show the pagination links even if there
         * aren't any pages to paginate through.
         * 
         * @access  public
         * @return  void
         */
        public function alwaysShowPagination()
        {
            $this->_variables['alwaysShowPagination'] = true;
        }

        /**
         * getCanonicalUrl
         * 
         * @access  public
         * @return  string
         */
        public function getCanonicalUrl()
        {
            $target = $this->_variables['target'];
            if (empty($target) === true) {
                $target = $_SERVER['PHP_SELF'];
            }
            $page = (int) $this->_variables['current'];
            if ($page !== 1) {
                return 'http://' . ($_SERVER['HTTP_HOST']) . ($target) . $this->getPageParam();
            }
            return 'http://' . ($_SERVER['HTTP_HOST']) . ($target);
        }

        /**
         * getPageParam
         * 
         * @access  public
         * @param   boolean|integer $page (default: false)
         * @return  string
         */
        public function getPageParam($page = false)
        {
            if ($page === false) {
                $page = (int) $this->_variables['current'];
            }
            $key = trim($this->_variables['key']);
            return (!empty($key) ? '?' . ($key) . '=' . ((int) $page) : ((int) $page));
            //return '?' . ($key) . '=' . ((int) $page);
        }

        /**
         * getPageUrl
         * 
         * @access  public
         * @param   boolean|integer $page (default: false)
         * @return  string
         */
        public function getPageUrl($page = false)
        {
            $target = $this->_variables['target'];
            if (empty($target) === true) {
                $target = $_SERVER['PHP_SELF'];
            }
            return 'http://' . ($_SERVER['HTTP_HOST']) . ($target) . ($this->getPageParam($page));
        }

        /**
         * getRelPrevNextLinkTags
         * 
         * @see     http://support.google.com/webmasters/bin/answer.py?hl=en&answer=1663744
         * @see     http://googlewebmastercentral.blogspot.ca/2011/09/pagination-with-relnext-and-relprev.html
         * @see     http://support.google.com/webmasters/bin/answer.py?hl=en&answer=139394
         * @access  public
         * @return  array
         */
        public function getRelPrevNextLinkTags()
        {
            // generate path
            $target = $this->_variables['target'];
            if (empty($target) === true) {
                $target = $_SERVER['PHP_SELF'];
            }
            $key = $this->_variables['key'];
            $params = $this->_variables['get'];
            $params[$key] = 'pgnmbr';
            $href = ($target) . '?' . http_build_query($params);
            $href = preg_replace(
                array('/=$/', '/=&/'),
                array('', '&'),
                $href
            );
            $href = 'http://' . ($_SERVER['HTTP_HOST']) . $href;

            // Pages
            $currentPage = (int) $this->_variables['current'];
            $numberOfPages = (
                (int) ceil(
                    $this->_variables['total'] /
                    $this->_variables['rpp']
                )
            );

            // On first page
            if ($currentPage === 1) {

                // There is a page after this one
                if ($numberOfPages > 1) {
                    $href = str_replace('pgnmbr', 2, $href);
                    return array(
                        '<link rel="next" href="' . ($href) . '" />'
                    );
                }
                return array();
            }

            // Store em
            $prevNextTags = array(
                '<link rel="prev" href="' . (str_replace('pgnmbr', $currentPage - 1, $href)) . '" />'
            );

            // There is a page after this one
            if ($numberOfPages > $currentPage) {
                array_push(
                    $prevNextTags,
                    '<link rel="next" href="' . (str_replace('pgnmbr', $currentPage + 1, $href)) . '" />'
                );
            }
            return $prevNextTags;
        }

        /**
         * parse
         * 
         * Parses the pagination markup based on the parameters set and the
         * logic found in the render.inc.php file.
         * 
         * @access  public
         * @return  void
         */
        public function parse()
        {
            // ensure required parameters were set
            $this->_check();

            // bring variables forward
            foreach ($this->_variables as $_name => $_value) {
                $$_name = $_value;
            }

            // buffer handling
            ob_start();
            include 'render.inc.php';
            $_response = ob_get_contents();
            ob_end_clean();
            return $_response;
        }

        /**
         * setClasses
         * 
         * @see     http://twitter.github.com/bootstrap/components.html#pagination
         * @access  public
         * @param   mixed $classes
         * @return  void
         */
        public function setClasses($classes)
        {
            $this->_variables['classes'] = (array) $classes;
        }

        /**
         * setClean
         * 
         * Sets the pagination to exclude page numbers, and only output
         * previous/next markup. The counter-method of this is self::setFull.
         * 
         * @access  public
         * @return  void
         */
        public function setClean()
        {
            $this->_variables['clean'] = true;
        }

        /**
         * setCrumbs
         * 
         * Sets the maximum number of 'crumbs' (eg. numerical page items)
         * available.
         * 
         * @access  public
         * @param   integer $crumbs
         * @return  void
         */
        public function setCrumbs($crumbs)
        {
            $this->_variables['crumbs'] = $crumbs;
        }

        /**
         * setCurrent
         * 
         * Sets the current page being viewed.
         * 
         * @access  public
         * @param   integer $current
         * @return  void
         */
        public function setCurrent($current)
        {
            $this->_variables['current'] = (int)$current;
        }

        /**
         * setFull
         * 
         * See self::setClean for documentation.
         * 
         * @access  public
         * @return  void
         */
        public function setFull()
        {
            $this->_variables['clean'] = false;
        }

        /**
         * setKey
         * 
         * Sets the key of the <_GET> array that contains, and ought to contain,
         * paging information (eg. which page is being viewed).
         * 
         * @access  public
         * @param   string $key
         * @return  void
         */
        public function setKey($key)
        {
            $this->_variables['key'] = $key;
        }

        /**
         * setNext
         * 
         * Sets the copy of the next anchor.
         * 
         * @access  public
         * @param   string $str
         * @return  void
         */
        public function setNext($str)
        {
            $this->_variables['next'] = $str;
        }

        /**
         * setNext
         * 
         * Sets the label text of the next anchor.
         * 
         * @access  public
         * @param   string $str
         * @return  void
         */
        public function setNextLabel($str)
        {
            $this->_variables['next_label'] = $str;
        }

        /**
         * setPrevious
         * 
         * Sets the copy of the previous anchor.
         * 
         * @access  public
         * @param   string $str
         * @return  void
         */
        public function setPrevious($str)
        {
            $this->_variables['previous'] = $str;
        }

        /**
         * setPrevious
         * 
         * Sets the label text of the previous anchor.
         * 
         * @access  public
         * @param   string $str
         * @return  void
         */
        public function setPreviousLabel($str)
        {
            $this->_variables['previous_label'] = $str;
        }

        /**
         * setRpp
         * 
         * Sets the number of records per page (used for determining total
         * number of pages).
         * 
         * @access  public
         * @param   integer $rpp
         * @return  void
         */
        public function setRpp($rpp)
        {
            $this->_variables['rpp'] = (int)$rpp;
        }

        /**
         * setTarget
         * 
         * Sets the leading path for anchors.
         * 
         * @access  public
         * @param   string $target
         * @return  void
         */
        public function setTarget($target)
        {
            $this->_variables['target'] = $target;
        }

        /**
         * setTotal
         * 
         * Sets the total number of records available for pagination.
         * 
         * @access  public
         * @param   integer $total
         * @return  void
         */
        public function setTotal($total)
        {
            $this->_variables['total'] = (int)$total;
        }
    }
