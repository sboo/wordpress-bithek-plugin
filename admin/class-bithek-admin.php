<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/sboo
 * @since      1.0.0
 *
 * @package    Bithek
 * @subpackage Bithek/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Bithek
 * @subpackage Bithek/admin
 * @author     Ramon Ackermann <ramon.ackermann@sboo.eu>
 */
class Bithek_Admin
{

    private $debug = false;

    private $required_columns = array(
        'ISBN',
        'Titel',
        'Verfasser I',
        'Verfasser II',
        'Verfasser III',
        'Medienart',
        'ausgeliehen?',
        'Schlagwort',
        'SK I',
        'SK II'
    );

    /**
     * @var array
     */
    public $errorMessages = array();

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * @var string
     */
    private $db_directory;

    /**
     * @var DateTime
     */
    private $last_update_datetime;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string $plugin_name The name of this plugin.
     * @param      string $version The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->db_directory = dirname(plugin_dir_path(__FILE__)) . '/db';

        if (file_exists($this->db_directory . '/books.db3')) {
            $this->last_update_datetime = new \DateTime('@' . filemtime($this->db_directory . '/books.db3'));
            $this->last_update_datetime->setTimezone(new \DateTimeZone('Europe/Zurich'));
        }

    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Bithek_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Bithek_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/bithek-admin.css', array(), $this->version,
            'all');

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Bithek_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Bithek_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/bithek-admin.js', array('jquery'),
            $this->version, false);

    }

    /**
     * @return array
     */
    protected function get_bithek_caps() {

        $bithek_caps = array(
            'bithek_import' => 1,
        );

        return $bithek_caps;
    }

    /**
     *
     */
    public function init_bithek_caps() {
        global $wp_roles;

        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        if (!isset($wp_roles->roles['administrator'])) {
            return;
        }

        $old_use_db = $wp_roles->use_db;
        $wp_roles->use_db = true;
        $administrator = $wp_roles->role_objects['administrator'];
        $bithek_caps = $this->get_bithek_caps();
        foreach(array_keys($bithek_caps) as $cap) {
            if (!$administrator->has_cap($cap)) {
                $administrator->add_cap($cap, true);
            }
        }
        $wp_roles->use_db = $old_use_db;
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @since    1.0.0
     */

    public function add_plugin_admin_menu()
    {
        add_menu_page('BiThek Import', 'BiThek Import', 'bithek_import', $this->plugin_name,
            array($this, 'display_plugin_setup_page'), 'dashicons-book', 50
        );
    }

    /**
     * Add settings action link to the plugins page.
     *
     * @since    1.0.0
     */

    public function add_action_links($links)
    {
        /*
        *  Documentation : https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
        */
        $settings_link = array(
            '<a href="' . admin_url('options-general.php?page=' . $this->plugin_name) . '">' . __('Settings',
                $this->plugin_name) . '</a>',
        );
        return array_merge($settings_link, $links);

    }

    /**
     * Render the settings page for this plugin.
     *
     * @since    1.0.0
     */

    public function display_plugin_setup_page()
    {
        include_once('partials/bithek-admin-display.php');
    }

    /**
     *
     */
    public function process_bithek_settings()
    {
        if (isset($_FILES['bithek-import']) && !$_FILES['bithek-import']['error']) {
            try {
                $this->process_import($_FILES['bithek-import']['tmp_name']);
                add_action('admin_notices', array($this, 'admin_notice_success'));
            } catch (\Exception $e) {
                $this->errorMessages[] = $e->getMessage();
                add_action('admin_notices', array($this, 'admin_notice_error'));
            }
        }
    }

    /**
     *
     */
    public function admin_notice_success()
    {
        $class = 'notice notice-success is-dismissible';
        $message = __('Neue Daten sind importiert', 'bithek');

        printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
    }

    public function admin_notice_error()
    {
        while (null !== ($message = array_pop($this->errorMessages))) {
            $class = 'notice notice-error is-dismissible';
            printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
        }
    }


    /**
     * @param $file_path
     * @throws Exception
     */
    private function process_import($file_path)
    {

        set_time_limit(60 * 15);//15 minutes


        if ($this->debug) {
            echo '<pre>';
        }
        @unlink($this->db_directory . '/bibi-clean.xml');
        $xml = file_get_contents($file_path);
        $xml = self::stripInvalidXml($xml);
        file_put_contents($this->db_directory . '/bibi-clean.xml', $xml);

        $xmlDoc = new DOMDocument();
        $xmlDoc->load($this->db_directory . '/bibi-clean.xml');

        $colNames = [];
        $colTypes = [];
        $colNameTranslations = [];
        $xmlCols = $xmlDoc->getElementsByTagName('METADATA')->item(0)->getElementsByTagName('FIELD');

        foreach ($xmlCols as $idx => $col) {
            $colNames[$idx] = $col->getAttribute('NAME');
            $colTypes[$idx] = $col->getAttribute('TYPE');
            $colNameTranslations[$idx] = self::slugify($col->getAttribute('NAME'));
        }

        $col_diff = array_diff($this->required_columns, $colNames);

        if (count($col_diff)) {
            throw new \Exception('Die folgenden Felder fehlen im Export: ' . implode(', ', $col_diff));
        }


        unlink($this->db_directory . '/books.db3');
        $dbh = new PDO('sqlite:' . $this->db_directory . '/books.db3');
        if (!$dbh) {
            if ($this->debug) {
                echo "\nnew PDO::errorInfo():\n";
                print_r($dbh->errorInfo());
                die;
            }
            throw new \Exception(PDO::errorInfo());
        }


        $stmt = $dbh->exec('PRAGMA synchronous = OFF');
        if ($stmt === false) {
            if ($this->debug) {
                echo "\npragma PDO::errorInfo():\n";
                print_r($dbh->errorInfo());
                die;
            }
            throw new \Exception(PDO::errorInfo());
        }

        $columnDefinitions = [];
        foreach ($colNames as $idx => $colName) {
            switch ($colTypes[$idx]) {
                case 'TEXT':
                    $type = 'TEXT';
                    break;
                case 'DATE':
                    $type = 'DATE';
                    break;
                case 'NUMBER':
                    $type = 'INTEGER';
                    break;
                case 'TIMESTAMP':
                    $type = 'DATETIME';
                    break;
                default:
                    $type = 'BLOB';
                    break;
            }
            $colName = $colNameTranslations[$idx];

            $columnDefinitions[] = $colName . ' ' . $type;
        }


        $createQuery = 'CREATE TABLE books ( recordid INTEGER PRIMARY KEY, ' . implode(',',
                $columnDefinitions) . ')';

        if ($this->debug) {
            echo '$createQuery=' . $createQuery . "\r\n";
        }

        $stmt = $dbh->exec($createQuery);
        if ($stmt === false) {
            if ($this->debug) {
                echo "\ncreate PDO::errorInfo():\n";
                print_r($dbh->errorInfo());
                die;
            }
            throw new \Exception($dbh->errorInfo());

        }

        $xmlItems = $xmlDoc->getElementsByTagName('RESULTSET')->item(0)->getElementsByTagName('ROW');

        $insertQuery = 'INSERT INTO books (recordid, ' . implode(',',
                $colNameTranslations) . ') VALUES (:recordid, :' . implode(', :', $colNameTranslations) . ')';
        if ($this->debug) {
            echo '$insertQuery=' . $insertQuery . "\r\n";
        }
        $stmt = $dbh->prepare($insertQuery);
        if ($stmt === false) {
            if ($this->debug) {
                echo "\ninsert prepare PDO::errorInfo():\n";
                print_r($dbh->errorInfo());
                die();
            }
            throw new \Exception($dbh->errorInfo());
        }

        foreach ($xmlItems as $xmlItem) {
            $recordId = $xmlItem->getAttribute('RECORDID');
            if ($this->debug) {
                echo '$recordId=' . $recordId . "\r\n";
            }
            $stmt->bindValue(':recordid', $recordId);

            $itemData = $xmlItem->getElementsByTagName('COL');
            foreach ($itemData as $idx => $col) {

                $data = $col->getElementsByTagName('DATA')->item(0);
                if ($data) {
                    switch ($colTypes[$idx]) {
                        case 'TEXT':
                            $value = $data->nodeValue;
                            break;
                        case 'DATE':
                            //23/11/2015
                            $value = null;
                            if ($data->nodeValue) {
                                $date = DateTime::createFromFormat('d/m/Y', $data->nodeValue);
                                if ($date) {
                                    $value = $date->format('Y-m-d');
                                }
                            }
                            break;
                        case 'NUMBER':
                            $value = $data->nodeValue;
                            break;
                        case 'TIMESTAMP':
                            $value = null;
                            //23/11/2015 09:34:46
                            if ($data->nodeValue) {
                                $date = DateTime::createFromFormat('d/m/Y H:i:s', $data->nodeValue);
                                if (!$date) {
                                    //try with just date
                                    $date = DateTime::createFromFormat('d/m/Y', $data->nodeValue);
                                }
                                if ($date) {
                                    $value = $date->format('Y-m-d H:i:s');
                                }
                            }
                            break;
                        default:
                            $value = $data->nodeValue;
                            break;
                    }
                } else {
                    $value = null;
                }
                $stmt->bindValue(':' . $colNameTranslations[$idx], $value);
            }

            $ok = $stmt->execute();
            if ($ok === false) {
                if ($this->debug) {
                    echo "\ninsert PDO::errorInfo():\n";
                    die;
                }
                throw new \Exception(PDO::errorInfo());
            }
            if ($this->debug) {
                echo 'inserted' . "\r\n\r\n";
                flush();
            }

        }
        if ($this->debug) {
            die('finished');
        }
    }

    /**
     * Removes invalid XML
     *
     * @access public
     * @param string $value
     * @return string
     */
    private static function stripInvalidXml($value)
    {
        $ret = "";
        $current;
        if (empty($value)) {
            return $ret;
        }

        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {
            $current = ord($value{$i});
            if (($current == 0x9) ||
                ($current == 0xA) ||
                ($current == 0xD) ||
                (($current >= 0x20) && ($current <= 0xD7FF)) ||
                (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                (($current >= 0x10000) && ($current <= 0x10FFFF))
            ) {
                $ret .= chr($current);
            } else {
                $ret .= " ";
            }
        }
        return $ret;
    }

    private static function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        $text = str_replace('-', '_', $text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }
}
