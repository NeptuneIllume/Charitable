<?php
/**
 * Sets up translations for Charitable.
 *
 * @package     Charitable/Classes/Charitable_i18n
 * @version     1.1.2
 * @author      Eric Daams
 * @copyright   Copyright (c) 2014, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License  
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Charitable_i18n' ) ) : 

/**
 * Charitable_i18n
 *
 * @since       1.1.2
 */
class Charitable_i18n extends Charitable_Start_Object {

    /**
     * @var     string
     */
    CONST TEXTDOMAIN = 'charitable';

    /**
     * The path to the languages directory. 
     *
     * @var     string
     * @access  private
     */
    private $languages_directory;

    /**
     * The site locale.
     *
     * @var     string
     * @access  private
     */
    private $locale;

    /**
     * The MO filename.
     *
     * @var     string
     * @access  private
     */
    private $mofile;

    /**
     * Set up the class. 
     *
     * @access  protected
     * @since   1.1.2
     */
    protected function __construct() {
        $this->languages_directory = apply_filters( 'charitable_languages_directory', charitable()->get_path( 'directory' ) . 'i18n/languages/' );
        $this->locale = apply_filters( 'plugin_locale', get_locale(), self::TEXTDOMAIN );
        $this->mofile = sprintf( '%1$s-%2$s.mo', self::TEXTDOMAIN, $this->locale );

        $this->load_textdomain();
    }

    /**
     * Create class object.
     * 
     * @return  void
     * @access  public
     * @since   1.1.2
     */
    public function load_textdomain() {
        foreach ( array( 'global', 'local' ) as $source ) {
            
            $mofile_path = $this->get_mofile_path( $source );

            if ( ! file_exists( $mofile_path ) ) {
                continue;
            }
         
            load_textdomain( self::TEXTDOMAIN, $mofile_path );
        }

        load_plugin_textdomain( self::TEXTDOMAIN, false, $this->languages_directory );
    }

    /**
     * Get the path to the MO file.
     *
     * @param   string $source Either 'local' or 'global'. 
     * @return  string
     * @access  private
     * @since   1.1.2
     */
    private function get_mofile_path( $source = 'local' ) {
        if ( 'global' == $source ) {
            return WP_LANG_DIR . '/' . self::TEXTDOMAIN . '/' . $this->mofile;
        }

        return $this->languages_directory . $this->mofile;
    }
}

endif; // End class_exists check