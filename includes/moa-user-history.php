<?php
class UserHistoryModule
{
    private $wpdb;
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'moa_user_history';

        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_custom_scripts'));

        // WP Hook to capture page visits, hooking into every page-load event
        //add_action('wp', array($this, 'log_user_page_visit'));

        //  AJAX Hook to callback routine for AJAX caller triggering on <a ...> links being clicked in the page
        add_action('wp_ajax_capture_link_click', array($this, 'capture_link_click'));
        add_action('wp_ajax_nopriv_capture_link_click', array($this, 'capture_link_click'));

        // WP Hook to display user history in admin Edit User page
        add_action('edit_user_profile', array($this, 'display_single_user_history'));

        // WP Hook to load JS script into footer to collapse/expand session block history - we use the admin-footer hook 
        add_action('admin_footer', array($this, 'user_history_admin_footer'));
    }

    public function enqueue_custom_scripts()
    {
        // WP Hook to enqueue supporting JS script that generates the AJAX calls for the event of the user events clicking on links
        wp_enqueue_script('custom-js', plugins_url('../js/moa-user-history.js', __FILE__), array('jquery'), '1.0.0', true);
        wp_localize_script('custom-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    /******************
     * AJAX Callback handler for AJAX call generated when a user clicks on a link in the website. This 
     * Captures the link and records it in the data table
     * @return void
     **************/
    public function capture_link_click()
    {
        $user_id = get_current_user_id();
        if ($user_id && isset($_POST['url'])) {
            $clicked_url = $_POST['url'];
            $session_id = session_id();
            $this->wpdb->insert(
                $this->table_name,
                array(
                    'user_id' => $user_id,
                    'session_id' => $session_id,
                    'clicked_element' => $clicked_url,
                    'page_id' => get_the_ID(),
                    'time' => current_time('mysql')
                )
            );

            echo json_encode(array('status' => 'success'));
            wp_die();
        }
    }

    /******************
     * Handler for the WP event when a new page is loaded, note this will only capture AJAX calls that refreshresult in a new page being loaded,
     * it won't capture AJAX calls that only refresh items on a page
     * @return void
     *****************/
    public function log_user_page_visit()
    {
        $user_id = get_current_user_id();
        if ($user_id) { // Ensure the user is logged in
            // Get the current URL along with any query string
            $current_page_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $session_id = session_id();

            // Insert the page visit into the database
            $this->wpdb->insert(
                $this->table_name,
                array(
                    'user_id' => $user_id,
                    'session_id' => $session_id,
                    'clicked_element' => $current_page_url,
                    'page_id' => get_the_ID(),
                    // Here, this column will hold the full page URL with query string
                    'time' => current_time('mysql')
                )
            );
        }
    }

    /************************
     * Function to display all the sessions a user has had on a per-user basis. Hooked via WP "adin-user-edit()" so automatically displayed on Admin User Edit Page.
     * The display shows all of the user's session, descending chron order, collapsed (ie the links visited history not visible), with time spent on  each link visible
     * (this is calculated as the difference of link and the next link's timestamps, with last link labelled as "N/A). A simple jQuery click handler
     * is included as a <script> block to handle the collapse/expand on the session header
     ***********************/
    public function display_single_user_history($user)
    {
        // Retrieve unique session IDs and their start time for the specified user, ordered by time
        $user_sessions = $this->wpdb->get_results($this->wpdb->prepare("SELECT session_id, MIN(time) as session_start FROM $this->table_name WHERE user_id = %d GROUP BY session_id ORDER BY session_start DESC", $user->ID));

        if ($user_sessions) {
            echo  '<h3>User Page Visit History</h3>';
            echo  '<div class="form-table">';
            foreach ($user_sessions as $session_data) {
                $session_id = $session_data->session_id;
                $weekday  = $this->weekday($session_data->session_start);
                $session_start = date("d/m/Y H:m", strtotime($session_data->session_start));

                echo '<div class="session" data-session-id="' . esc_attr($session_id) . '">';
                echo '
                <h4 style="cursor:pointer;text-decoration:underline;">
                    <span class="user-history-arrow">&#9660;</span> Session: ' . $weekday . ' ' . esc_html($session_start) . 
                '</h4>';
                // echo '<h4>Session: ' . $weekday . ' ' . esc_html($session_start) . ' Id: ' . esc_html($session_id)  . '</h4>';

                // Now fetch the browsing history for this specific session ID
                $visits = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM $this->table_name WHERE user_id = %d AND session_id = %s ORDER BY time ASC", $user->ID, $session_id));

?>
                <table class="visit-list" style="display: none;">
                    <tr>
                        <td>Page Title</td>
                        <td>Page URL</td>
                        <td>URL Querystring</td>
                        <td>Visit Time</td>
                        <td>Duration</td>
                    </tr>
                <?php
                for ($i = 0; $i < count($visits); $i++) {
                    $visit = $visits[$i];
                    $parsed_url = parse_url($visit->clicked_element);
                    $host = (isset($parsed_url['host'])) ? $parsed_url['host'] : site_url();
                    $base_url = "https://" . $host . $parsed_url['path'];
                    $query_string = isset($parsed_url['query']) ? $parsed_url['query'] : '';

                    $page_title = get_the_title(url_to_postid($base_url));
                    //if (!$page_title) $page_title = get_the_title( $visit->page_id );
                    echo '
                    <tr>
                        <td>' . esc_html($page_title)   . '</td>
                        <td>' . esc_html($base_url)     . '</td>
                        <td>' . esc_html($query_string) . '</td>
                        <td>' . esc_html($visit->time)  . '</td>';

                    if (isset($visits[$i + 1])) {
                        $next_visit_time = strtotime($visits[$i + 1]->time);
                        $current_visit_time = strtotime($visit->time);
                        $duration = gmdate("H:i:s", $next_visit_time - $current_visit_time);
                        echo '<td> ' . $duration . '</td>';
                    } else {
                        echo '<td>(N/A)</td>';
                    }
                    echo '</tr>';
                }
                echo '</table></div>';
            }
            echo '</div>';
                ?>
                <script type="text/javascript">
                    // jQuery code to expand/collapse session block, showing/hiding  all the links visited
                    jQuery(document).ready(function($) {
                        $('.session').on('click', function(event) {
                            // Check if the clicked element is the session container itself, not its children
                            if ($(event.target).is('h4, h4 > .user-history-arrow')) {
                                var arrow = $(this).find('.user-history-arrow');
                                $(this).find('.visit-list').slideToggle(function() {
                                    // Toggle arrow based on the visibility state of the list
                                    if ($(this).is(":visible")) {
                                        arrow.html('&#9650;'); // Upwards block arrow
                                    } else {
                                        arrow.html('&#9660;'); // DOwnwards block arrow
                                    }
                                });
                            }
                        });
                    });
                </script>
            <?php
        }
    }
    private function weekday($date)
    {
        $i = date("w", strtotime($date));
        $daysArr = ["Sun", "Mon", "Tue", "Wed", "Thur", "Fri", "Sat"];
        return $daysArr[$i];
    }

    /******************
     * Calling the display_all_users_histories method  will display histories grouped by session. 
     * Each session will have a header showing its session ID, followed by the browsing histories of all users during that session.
     * If added to an admin page this needs wrapping with appropriate HTML and WordPress admin styles.
     *****************/
    public function display_all_users_histories()
    {
        // Retrieve unique session IDs ordered by time, including the start date for each session
        $unique_sessions = $this->wpdb->get_results("SELECT session_id, user_id, MIN(time) as start_date FROM $this->table_name GROUP BY session_id, user_id ORDER BY start_date DESC");

        echo '<table class="form-table moa_user_history"><tr>';
        echo '<h3>All Users Browsing Histories</h3>';
        if ($unique_sessions) {

            foreach ($unique_sessions as $session) {
                $session_id = $session->session_id;
                $start_date = $session->start_date;
                $user_info = get_userdata($session->user_id);
                $display_name = $user_info ? $user_info->display_name : 'Unknown User';

                // Fetch histories corresponding to the current session ID
                $session_histories = $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM $this->table_name WHERE session_id = %s ORDER BY time ASC", $session_id));

                echo '<div class="session-history" data-session-id="' . esc_attr($session_id) . '">';
                echo '<h4>Session ID: ' . esc_html($session_id) . ' - User: ' . esc_html($display_name) . ' - Start Date: ' . esc_html($start_date) . '</h4>';

                foreach ($session_histories as $history) {
                    $parsed_url = parse_url($history->clicked_element);
                    $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
                    $query_string = isset($parsed_url['query']) ? $parsed_url['query'] : '';

                    echo '<div class="individual-history">';
                    echo '<strong>Visited:</strong> ' . esc_html($base_url) . '<br>';
                    echo '<strong>Arguments:</strong> ' . esc_html($query_string) . '<br>';
                    echo '<strong>Time:</strong> ' . esc_html($history->time) . '<br>';
                    echo '</div>';
                }

                echo '</div><hr>';
            }
        } else {
            echo '<p>No browsing histories found.</p>';
        }
        echo "</tr></table>";
    }

    public function user_history_admin_footer()
    {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('.session').on('click', function(event) {
                        // Check if the clicked element is the session container itself, not its children
                        if ($(event.target).is('.session')) {
                            $(this).find('.visit-list').slideToggle();
                        }
                    });
                });
            </script>
    <?php
    }
}



// Initialize the class
$user_history_module = new UserHistoryModule();

/************************
 * Code to create the table to hold user_histories
 */
function create_user_history_table()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'moa_user_history';

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        session_id varchar(255) DEFAULT '' NOT NULL,
        clicked_element text NOT NULL,
        page_id mediumint(9) NOT NULL,
        time datetime DEFAULT '0000-00-00 00:00:00' NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
// register_activation_hook(__FILE__, 'create_user_history_table');
