<?php
/* Plugin Name: Analytics injector 
 * Description: Plain Jane Analytics Injector
 * Author: David Young
 * Version: 1.0
 */

class analytics_injector {
  private $sections;
  private $chekcboxes;
  private $settings;
  
  public function __construct() {
    $this->checkboxes = array();
    $this->settings = array();
    $this->get_settings();

    $this->sections['general'] = __('General Settings');

    if(is_admin()) {
      add_action('admin_menu', array(&$this, 'add_pages'));
      add_action('admin_init', array(&$this, 'register_settings'));
    } else {
      add_action('wp_head', array(&$this,'add_analytics_code'),99);
    }

    if(!get_option('analytics_injector_options')) {
      $this->initialize_settings();
    }
  }

  public function add_pages() {
    $admin_page = add_options_page( __('Analytics Injector Options'), __('Analytics Injector Options'), 'manage_options', 'analytics_injector_options', array(&$this, 'display_page'));
  }
  
  public function create_setting($args = array()) {
    $defaults = array(
      'id'	=> 'default_field',
      'title'	=> __( 'Default Field' ),
      'desc'	=> __( 'This is a default description.' ),
      'std'	=> '',
      'type'	=> 'text',
      'section' => 'general',
      'choices' => array(),
      'class'	=> ''
    );
     	
    extract( wp_parse_args( $args, $defaults ) );

    $field_args = array(
      'type'	  => $type,
      'id'	  => $id,
      'desc'	  => $desc,
      'std'	  => $std,
      'choices'	  => $choices,
      'label_for' => $id,
      'class'	  => $class
    );

    if ( $type == 'checkbox' ){
      $this->checkboxes[] = $id;
    }

    add_settings_field( $id, $title, array( $this, 'display_setting' ), 'analytics_injector_options', $section, $field_args);
  }

  public function display_setting($args = array()) {
    extract($args);

    $options = get_option('analytics_injector_options');

    if(!isset($options[$id]) && $type != 'checkbox') {
      $options[$id] = $std;
    } elseif(!isset($options[$id])) {
      $options[$id] = 0;
    }

    $field_class = '';
    if($class != '') {
      $field_class = ' ' . $class;
    }

    switch($type) {
      case 'heading':
        echo '</td></tr><tr valign="top"><td colspan="2"><h4>' . $desc . '</h4>';
        break;
      case 'checkbox':
        echo '<input class="checkbox' . $field_class . '" type="checkbox" id="' . $id . '" name="analytics_injector_options[' . $id . ']" value="1" ' . checked( $options[$id], 1, false ) . ' /> <label for="' . $id . '">' . $desc . '</label>';
        break;
      case 'upload':
        echo '<input id="' . $id . '" class="upload_button' . $field_class . '"  type="file" name="upload_button " value="Upload" /"><br />';
        echo '<label for="' . $id . '">' . $desc . '</label><br />';
        echo '<span id="response_' . $class . '"></span>';
        break;
      case 'select':
        echo '<select class="select' . $field_class . '" name="analytics_injector_options[' . $id . ']">';
        foreach($choices as $value => $label) {
          echo '<option value="' . esc_attr($value) . '"' . selected($options[$id], $value, false) . '>' . $label . ' </option>';
        }
        echo '</select>';
        if($desc != '') {
          echo '<br /><span class="description">' . $desc . '</span>';
        }
        break;
      case 'text':
      default:
        echo '<input class="regular-text' . $field_class . '" type="text" id="' . $id . '" name="analytics_injector_options[' . $id . ']" placeholder="' . $std . '" value="' . esc_attr( $options[$id] ) . '" />';
        if($desc != '') {
          echo '<br /><span class="description">' . $desc .'</span>';
        }
        break;
    }
  }

  public function get_settings() {
    $this->settings['enable'] = array(
      'section' => 'general',
      'title'   => __('Enable Analytics'),
      'desc'    => __(''),
      'type'    => 'checkbox',
      'std'     => 0
    );
    $this->settings['ua'] = array(
      'section' => 'general',
      'title'   => __('UA'),
      'desc'    => __('UA from Google Analytics'),
      'type'    => 'text',
      'std'     => 'UA-XXXXXXXX-X'
    );
    $this->settings['domain'] = array(
      'section' => 'general',
      'title'   => __('Domain'),
      'desc'    => __('Domain from Google Analytics'),
      'type'    => 'text',
      'std'     => 'example.com'
    );
    $this->settings['attribution'] = array(
      'section' => 'general',
      'title'   => __('Enable Link attribution'),
      'desc'    => '',
      'type'    => 'checkbox',
      'std'     => 0
    );
  }

  public function initialize_settings() {
    $default_settings = array();
    foreach($this->settings as $id => $setting) {
      if($setting['type'] != 'heading') {
        $default_settings[$id] = $setting['std'];
      }
    }
    update_option('analytics_injector_options', $default_settings);
  }
 
  public function display_page() {
  ?>
  <div class="wrap">
    <div class="icon32" id="icon-options-general"></div>
    <h2><?php echo __('Analytics Injector Settings'); ?></h2>
    <form action="options.php" method="post">
  <?php settings_fields('analytics_injector_options'); ?>
  <?php do_settings_sections($_GET['page']); ?>
      <p class="submit">
        <input name="Submit" type="submit" class="button-primary" value="<?php echo __('Save Changes'); ?>" />
      </p>
    </form>
  </div>
  <?php
  }

  public function register_settings() {
    register_setting('analytics_injector_options','analytics_injector_options', array(&$this, 'validate_settings'));

    foreach($this->sections as $slug => $title) {
      add_settings_section($slug, $title, array(&$this, 'display_section'), 'analytics_injector_options');
    }
    $this->get_settings();
    foreach($this->settings as $id => $setting) {
      $setting['id'] = $id;
      $this->create_setting($setting);
    }
  }

  public function validate_settings($input) {
    if(!isset($input['reset_settings'])) {
      $options = get_option('analytics_injector_options');
      foreach($this->checkboxes as $id) {
        if(isset($options[$id]) && !isset($input[$id])) {
          unset($options[$id]);
        }
      }
      return $input;
    }
    return false;
  }

  function add_analytics_code() {
    # we need the UA string, which we stored in the database
    $opts = get_option('analytics_injector_options');
    $ua = $opts['ua']; 
    $domain = $opts['domain'];
    $enable = $opts['enable'];
    if($ua != "UA-XXXXXXXX-X" && $enable && $domain != "example.com") {
      if($attribution) {
        $attribution-line = "ga('require', 'linkid', linkid.js');";
      echo "
    <!-- Begin Analytics tracking code -->
    <script>
      (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
      m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
      })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

      ga('create', '$ua', '$domain');
      $attribution-line
      ga('send', 'pageview');

    </script>
    <!-- End Analytics tracking code -->
      ";
    } else {
      echo "<!-- Not inserting tracking code -->";
    }
  }
  
  public function display_section($args) {
  }
}
$plugin_options = new analytics_injector();
function analytics_injector_option($option) {
  $options = get_option('analytics_injector_options');
  if(isset($options[$option])) {
    return $options[$option];
  } else {
    return false;
  }
}
