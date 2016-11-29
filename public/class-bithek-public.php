<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://github.com/sboo
 * @since      1.0.0
 *
 * @package    Bithek
 * @subpackage Bithek/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Bithek
 * @subpackage Bithek/public
 * @author     Ramon Ackermann <ramon.ackermann@sboo.eu>
 */
class Bithek_Public
{

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

    private $db_directory;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string $plugin_name The name of the plugin.
     * @param      string $version The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->db_directory = dirname(plugin_dir_path(__FILE__)) . '/db';

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
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

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/bithek-public.css', array(),
            $this->version, 'all');

    }

    /**
     * Register the JavaScript for the public-facing side of the site.
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

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/bithek-public.js', array('jquery'),
            $this->version, false);

    }

    private function get_medienart_name($key = null)
    {
        $medienartOptions = array(
            '' => 'Alle',
            'Belletristik' => 'Belletristik',
            'Comic' => 'Comic',
            'DVD-Video' => 'DVD-Video',
            'Hörbuch' => 'Hörbuch',
            'Kass.' => 'Kassette',
            'Sachliteratur' => 'Sachliteratur',
            'Zeitschrift' => 'Zeitschrift',
        );
        if (!is_null($key) && isset($medienartOptions[$key])) {
            return $medienartOptions[$key];
        }
        return $medienartOptions;
    }

    /**
     * @param $shortcode_attributes
     * @return string
     */
    public function shortcode_bithek($shortcode_attributes)
    {
        // For empty Shortcodes like [table] or [table /], an empty string is passed, see WP Core #26927.
        $shortcode_attributes = (array)$shortcode_attributes;
        $action = isset($shortcode_attributes['action']) ? $shortcode_attributes['action'] : null;

        switch ($action) {
            case 'search':
                return $this->searchform() . $this->searchresult();
                break;
            default:
                break;
        }
    }

    /**
     * @return string
     */
    private function searchform()
    {
        $titel = isset($_POST['titel']) ? trim($_POST['titel']) : '';
        $verfasser_i = isset($_POST['verfasser_i']) ? trim($_POST['verfasser_i']) : '';
        $thema = isset($_POST['thema']) ? trim($_POST['thema']) : '';
        $medienart = isset($_POST['medienart']) ? trim($_POST['medienart']) : '';

        $medienartOptions = $this->get_medienart_name();

        $formHTML = '<form method="POST">
        <table class="bithek-searchform">
            <tr><td>Titel:</td><td><input type="text" name="titel" value="' . $titel . '"/></td></tr>
            <tr><td>Verfasser:</td><td><input type="text" name="verfasser_i" value="' . $verfasser_i . '"/></td></tr>
            <tr><td>Thema:</td><td><input type="text" name="thema" value="' . $thema . '"/></td></tr>
            <tr><td>Medienart:</td><td><select name="medienart">';
        foreach ($medienartOptions as $medienartOptionKey => $medienartOptionValue) {
            $formHTML .= '<option value="' . $medienartOptionKey . '" ' . ($medienart == $medienartOptionKey ? 'selected="selected"' : '') . '>' . $medienartOptionValue . '</option>';
        }
        $formHTML .= '
            </select></td></tr>
            <tr><td></td><td><input type="submit" value="Suche"></td></tr>
        </table>
        </form>';

        return $formHTML;
    }


    private function searchresult()
    {
        $titel = isset($_POST['titel']) ? trim($_POST['titel']) : '';
        $verfasser_i = isset($_POST['verfasser_i']) ? trim($_POST['verfasser_i']) : '';
        $thema = isset($_POST['thema']) ? trim($_POST['thema']) : '';
        $medienart = isset($_POST['medienart']) ? trim($_POST['medienart']) : '';

        $limit = 50;
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;

        $resultHTML = '';
        if (
            !empty($titel)
            || !empty($verfasser_i)
            || !empty($thema)
            /*|| !empty($medienart)*/
        ) {
            $dbh = new PDO('sqlite:' . $this->db_directory . '/books.db3');
            if (!$dbh) {
                echo "\nnew PDO::errorInfo():\n";
                print_r($dbh->errorInfo());
            }
            $where = [];
            $params = [];
            if (!empty($titel)) {
                $titel = preg_replace('/[^\d\s\p{L}\p{Nd}]/ui',' ', $titel);
                $arrTitle = explode(' ', $titel);
                $arrTitle = array_filter($arrTitle);
                foreach($arrTitle as $word) {
                    $where[] = 'titel LIKE ?';
                    $params[] = '%' . $word . '%';
                }
            }

            if (!empty($verfasser_i)) {
                $verfasser_i = preg_replace('/[^\d\s\p{L}\p{Nd}]/ui',' ', $verfasser_i);
                $arrVervasser_i = explode(' ', $verfasser_i);
                $arrVervasser_i = array_filter($arrVervasser_i);
                foreach($arrVervasser_i as $word) {
                    $where[] = 'verfasser_i LIKE ?';
                    $params[] = '%' . $word . '%';
                }
            }

            if (!empty($thema)) {
                $thema = preg_replace('/[^\d\s\p{L}\p{Nd}]/ui',' ', $thema);
                $arrThema = explode(' ', $thema);
                $arrThema = array_filter($arrThema);
                foreach($arrThema as $word) {
                    $where[] = '(schlagwort LIKE ? OR sk_i LIKE ? OR sk_ii LIKE ?)';
                    $params[] = '%' . $word . '%';
                    $params[] = '%' . $word . '%';
                    $params[] = '%' . $word . '%';
                }
            }

            if (!empty($medienart)) {
                $where[] = 'medienart = ?';
                $params[] = $medienart;
            }

            $query = 'SELECT recordid, isbn, titel, verfasser_i, medienart, ausgeliehen, schlagwort, sk_i, sk_ii FROM books';
            if (count($where)) {
                $query .= ' WHERE ' . implode(' AND ', $where);
            }

            $query .= ' LIMIT ' . (($page - 1) * $limit) . ', ' . $limit;

            $stmt = $dbh->prepare($query);
            if ($stmt === false) {
                echo "\nprepare PDO::errorInfo():\n";
                print_r($dbh->errorInfo());
            }

            $stmt->execute($params);

            $resultHTML .= '<h2>Suchresultat</h2><table class="bithek-resultlist">';
            while ($results = $stmt->fetch()) {

                $thema = array( $results['schlagwort'], $results['sk_i'], $results['sk_ii']);
                $thema = array_filter($thema);
                $resultHTML .= '<tr class="bithek-result" data-isbn="' . str_replace('-', '',
                        $results['isbn']) . '">';

                $resultHTML .= '<td class="book"><div class="verfasser_i">' . $results['verfasser_i'] . '</div>';
                $resultHTML .= '<div class="titel">' . $results['titel'] . '</div>';
                $resultHTML .= '<div class="medienart">' . $this->get_medienart_name($results['medienart']) . (count($thema) ? ' - ' . implode(', ', $thema) : '') .  '</div>';
                $resultHTML .= '</td><td class="status">';
                $resultHTML .= '<div class="ausgeliehen">' . $results['ausgeliehen'] . '</div>';
                $resultHTML .= '</td><td class="image">';
                $resultHTML .= '</td></tr>';
            }
            $resultHTML .= '</table>';
        }

        return $resultHTML;
    }
}
