<?PHP
/**
 * Licensed to The Apereo Foundation under one or more contributor license
 * agreements. See the NOTICE file distributed with this work for
 * additional information regarding copyright ownership.

 * The Apereo Foundation licenses this file to you under the Apache License,
 * Version 2.0 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.

 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
/**
*
* preview page, allows the site to make a preview page for a xerte module
*
* @author Patrick Lockley
* @version 1.0
* @params array row_play - The array from the last mysql query
* @package
*/

require_once(dirname(__FILE__) .  '/../../website_code/php/xmlInspector.php');

/**
*
* Function show_preview_code
* This function creates folders needed when creating a template
* @param array $row - an array from a mysql query for the template
* @param array $row_username - an array from a mysql query for the username
* @version 1.0
* @author Patrick Lockley
*/

require_once(dirname(__FILE__) . "/play.php");

function show_preview_code($row)
{
    global $xerte_toolkits_site;

    $template_dir = $xerte_toolkits_site->users_file_area_full . $row['template_id'] . "-" . $row['username'] . "-" . $row['template_name'] . "/";

    if(!file_exists($template_dir .'/preview.xml')) {

        $buffer = file_get_contents($template_dir . '/data.xml');
        $fp = fopen($template_dir . '/preview.xml','x');
        fwrite($fp, $buffer);
        fclose($fp);

    }

    $preview_filename = "preview.xml";

	//************ TEMPORARY ****************

	//if (file_exists($template_dir . '/preview2.xml')) {
	//	$preview_filename = "preview2.xml";
	//}

	//***************************************

    echo show_template_page($row, $preview_filename);
}

function show_preview_code2($row, $row_username){

    // TOR 20200826 NOT USED ANYMORE, SEE show_template_page of play.php
	global $xerte_toolkits_site, $youtube_api_key;

    _load_language_file("/modules/xerte/preview.inc");

    $template_dir = $xerte_toolkits_site->users_file_area_full . $row['template_id'] . "-" . $row['username'] . "-" . $row['template_name'] . "/";

    /*
    * Format the XML strings to provide data to the engine
    */

	if(!file_exists($template_dir . '/preview.xml')) {

		$buffer = file_get_contents($template_dir . "/data.xml");

		$fp = fopen($template_dir . "/preview.xml","x");
		fwrite($fp, $buffer);
		fclose($fp);

	}

    $string_for_flash = $xerte_toolkits_site->users_file_area_short . $row['template_id'] . "-" . $row_username['username'] . "-" . $row['template_name'] . "/";

    $xmlfile = $string_for_flash . "preview.xml";

    $xmlFixer = new XerteXMLInspector();
    $xmlFixer->loadTemplateXML($xmlfile, true);

    if (strlen($xmlFixer->getName()) > 0)
    {
        $title = $xmlFixer->getName();
    }
    else
    {
        $title = XERTE_PREVIEW_TITLE;
    }
    $string_for_flash_xml = $xmlfile . "?time=" . time();

    $flash_js_dir = "modules/" . $row['template_framework'] . "/";
    $template_path = "modules/" . $row['template_framework'] . "/parent_templates/" . $row['parent_template'] . "/";
    $rlo_file = $template_path . $row['template_name'] . ".rlt";

    list($x, $y) = explode("~",get_template_screen_size($row['template_name'],$row['template_framework']));

    // determine the correct engine to use
    $engine = 'flash';
    $extra_flags = explode(";", $row['extra_flags']);
    foreach($extra_flags as $flag)
    {
        $parameter = explode("=", $flag);
        switch($parameter[0])
        {
            case 'engine':
                $engine = $parameter[1];
                break;
        }
    }
    // If given as a parameter, force this engine
    // If given as a parameter, force this engine
    if (isset($_REQUEST['engine']))
    {
        if ($_REQUEST['engine'] == 'other')
        {
            if ($engine == 'flash')
                $engine = 'javascript';
            else
                $engine = 'flash';
        }
        else
        {
            $engine=$_REQUEST['engine'];
        }
    }
    if ($engine == 'flash')
    {
        $version = getVersion();
        $language_ISO639_1code = substr($xmlFixer->getLanguage(), 0, 2);
        $page_content = file_get_contents($xerte_toolkits_site->basic_template_path . $row['template_framework'] . "/player/rloObject.htm");

        $page_content = str_replace("%WIDTH%", $x, $page_content);
        $page_content = str_replace("%HEIGHT%", $y, $page_content);
        $page_content = str_replace("%TITLE%", $title , $page_content);
        $page_content = str_replace("%RLOFILE%", $rlo_file, $page_content);
        $page_content = str_replace("%JSDIR%", $flash_js_dir, $page_content);
        $page_content = str_replace("%XMLPATH%", $string_for_flash, $page_content);
        $page_content = str_replace("%XMLFILE%", $string_for_flash_xml, $page_content);
        $page_content = str_replace("%SITE%",$xerte_toolkits_site->site_url,$page_content);

        $tracking = "<script type=\"text/javascript\" src=\"" . $template_path . "common_html5/js/xttracking_noop.js?version=" . $version . "\"></script>";

        $page_content = str_replace("%TRACKING_SUPPORT%", $tracking, $page_content);
        $page_content = str_replace("%EMBED_SUPPORT%", "", $page_content);
    }
    else
    {
        $version = getVersion();
        $language_ISO639_1code = substr($xmlFixer->getLanguage(), 0, 2);

        // $engine is assumed to be javascript if flash is NOT set
        $page_content = file_get_contents($xerte_toolkits_site->basic_template_path . $row['template_framework'] . "/player_html5/rloObject.htm");

        // Check for default logo
        $page_content = process_logos($template_path, $page_content);

        $page_content = str_replace("%VERSION%", $version , $page_content);        // $engine is assumed to be html5 if flash is NOT set
        $page_content = str_replace("%LANGUAGE%", $language_ISO639_1code, $page_content);
        $page_content = str_replace("%VERSION_PARAM%", "?version=" . $version , $page_content);        // $engine is assumed to be html5 if flash is NOT set
        $page_content = str_replace("%TITLE%", $title , $page_content);
        $page_content = str_replace("%TEMPLATEPATH%", $template_path, $page_content);
        $page_content = str_replace("%TEMPLATEID%", $_GET['template_id'], $page_content);
        $page_content = str_replace("%XMLPATH%", $string_for_flash, $page_content);
        $page_content = str_replace("%XMLFILE%", $string_for_flash_xml, $page_content);
        $page_content = str_replace("%THEMEPATH%", "themes/" . $row['parent_template'] . "/",$page_content);

        // Handle offline variables
        $page_content = str_replace("%OFFLINESCRIPTS%", "", $page_content);
        $page_content = str_replace("%OFFLINEINCLUDES%", "", $page_content);
        $page_content = str_replace("%MATHJAXPATH%", "https://cdn.jsdelivr.net/npm/mathjax@2/", $page_content);

        $tracking = "<script type=\"text/javascript\" src=\"" . $template_path . "common_html5/js/xttracking_noop.js?version=" . $version . "\"></script>";

        $page_content = str_replace("%TRACKING_SUPPORT%", $tracking, $page_content);
        $page_content = str_replace("%YOUTUBEAPIKEY%", $youtube_api_key, $page_content);
        $page_content = str_replace("%EMBED_SUPPORT%", "", $page_content);

        // Check popcorn mediasite and peertube config files
        $popcorn_config = "";
        $mediasite_config_js = $template_path . "common_html5/js/popcorn/config/mediasite_urls.js";
        if (file_exists($mediasite_config_js))
        {
            $popcorn_config .= "<script type=\"text/javascript\" src=\"$mediasite_config_js?version=" . $version . "\"></script>\n";
        }
        $peertube_config_js = $template_path . "common_html5/js/popcorn/config/peertube_urls.js";
        if (file_exists($peertube_config_js))
        {
            $popcorn_config .= "<script type=\"text/javascript\" src=\"$peertube_config_js?version=" . $version . "\"></script>\n";
        }
        $page_content = str_replace("%POPCORN_CONFIG%", $popcorn_config, $page_content);

    }
    echo $page_content;
}

