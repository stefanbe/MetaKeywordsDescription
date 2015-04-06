<?php if(!defined('IS_CMS')) die();

/***************************************************************
*
* Keywords und Description Plugin für moziloCMS.
*
***************************************************************/

class MetaKeywordsDescription extends Plugin {

    function getContent($value) {

        if(defined("PLUGINADMIN")) {
            $settings_array = $this->settings->toArray();
            if(getRequestValue('savekeydescript',"post",false)) {
                if(getRequestValue('keydescript',"post",false) and is_array(getRequestValue('keydescript',"post",false))) {
                    $post_array = getRequestValue('keydescript',"post",false);
                    # aufräumen
                    foreach($settings_array as $cat_page => $tmp) {
                        if(strstr($cat_page,FILE_START) !== false and strstr($cat_page,FILE_END) !== false) {
                            if(!array_key_exists($cat_page, $post_array))
                                $this->settings->delete($cat_page);
                        }
                    }
                    foreach($post_array as $cat_page => $value) {
                        if(strlen($value['keywords']) > 2 or strlen($value['description']) > 2 ) {
                            $value['keywords'] = htmlentities($value['keywords'],ENT_QUOTES,CHARSET);
                            $value['description'] = htmlentities($value['description'],ENT_QUOTES,CHARSET);
                            $this->settings->set($cat_page,$value);
                        } else
                            $this->settings->delete($cat_page);
                    }
                }
            } elseif(getRequestValue('clearkeydescript',"post",false)) {
                foreach($settings_array as $cat_page => $tmp) {
                    if(strstr($cat_page,FILE_START) !== false and strstr($cat_page,FILE_END) !== false)
                    $this->settings->delete($cat_page);
                }
            }
            return $this->getAdmin();
        }

        if($value == "plugin_first") {
            global $CatPage;
$cat = getRequestValue("cat","get",false);
if(empty($cat))
    $cat = false;
#            $cat = $_GET['cat'];
            if(!$cat) {
                global $CMS_CONF;
                $cat = $CMS_CONF->get("defaultcat");
            }
$page = getRequestValue("cat","get",false);
if(empty($page))
    $page = false;
#            $page = $_GET['page'];
            if(!$page and $CatPage->exists_CatPage($cat,false)) {
                $page = $CatPage->get_FirstPageOfCat($cat);
            }
            if($this->settings->keyExists(FILE_START.$cat.":".$page.FILE_END)) {
                $setting = $this->settings->get(FILE_START.$cat.":".$page.FILE_END);
                if(is_array($setting)) {
                    global $template;
                    if(strlen($setting['description']) > 2)
                        $template = str_replace('{WEBSITE_DESCRIPTION}',$setting['description'],$template);
                    if(strlen($setting['keywords']) > 2)
                        $template = str_replace('{WEBSITE_KEYWORDS}',$setting['keywords'],$template);
                }
            }
        }
        return null;
    }

    function getConfig() {
        if(IS_ADMIN and $this->settings->get("plugin_first") !== "true") {
            $this->settings->set("plugin_first","true");
        }
        $config = array();
        $config["--admin~~"] = array(
            "buttontext" => "Bearbeiten",
            "description" => "Suchworte (keywords) und Kurzbeschreibung (description) für jede Inhaltsseite:",
            "datei_admin" => "index.php"
        );
        return $config;
    }

    function getInfo() {
            $info = array(
            // Plugin-Name + Version
            "<b>MetaKeywordsDescription</b> Revision 2",
            // moziloCMS-Version
            "2.0",
            // Kurzbeschreibung nur <span> und <br /> sind erlaubt
            'Eigene Meta Keywords und Description für jede Inhaltsseite.<br />
            In dem <b>Bearbeiten</b> Fenster kanst du für jede Inhaltseite deine Eigenen Keywords und Description eintragen.<br />
            Wenn Du nichts Einträgst werden die aus dem admin genommen.<br />
            Die Keywords trenst Du mit einem Komma.<br />
            Du brauchst für dieses Plugin <b>keinen Platzhalter</b> da es die Platzhalter "<b>{WEBSITE_KEYWORDS}</b>" und "<b>{WEBSITE_DESCRIPTION}</b>" benutzt.',
            // Name des Autors
            "stefanbe",
            // Download-URL
            "http://www.mozilo.de/forum/index.php?action=media",
            // Platzhalter für die Selectbox in der Editieransicht 
            // - ist das Array leer, erscheint das Plugin nicht in der Selectbox
            array()
        );
        return $info;
    }

    function getAdmin() {
        global $CatPage;

        $description = array();
        $keywords = array();
        if($this->settings->get("description") and is_array($this->settings->get("description")))
            $description = $this->settings->get("description");
        if($this->settings->get("keywords") and is_array($this->settings->get("keywords")))
            $keywords = $this->settings->get("keywords");

        $html = '<div id="admin-key-descr">'
        .'<form name="allentries" action="'.URL_BASE.ADMIN_DIR_NAME.'/index.php" method="POST">'
        .'<input type="hidden" name="pluginadmin" value="'.PLUGINADMIN.'" />'
        .'<input type="hidden" name="action" value="'.ACTION.'" />'
        .'<div id="admin-key-descr-menu" class="ui-widget-content ui-corner-all">'
        .'<input type="submit" class="admin-key-descr-submit" name="savekeydescript" value="Speichern" /> '
        .'<input type="submit" class="admin-key-descr-submit" name="clearkeydescript" value="Alle Einträge Löschen" /><br />'
        .'</div>'
        .'<div id="admin-key-descr-content">'
        .'<ul id="admin-key-descr-content-ul" class="mo-ul">';
        foreach ($CatPage->get_CatArray(false,false,array(EXT_PAGE,EXT_HIDDEN)) as $cat) {
            $html .= '<li class="mo-li ui-widget-content ui-corner-all"><div class="admin-key-descr-cat">'.$CatPage->get_HrefText($cat,false).'</div>'
            .'<ul class="mo-in-ul-ul">';
            foreach ($CatPage->get_PageArray($cat,array(EXT_PAGE,EXT_HIDDEN),true) as $page) {
                $html .= '<li class="mo-in-ul-li mo-inline ui-widget-content ui-corner-all ui-helper-clearfix"><b>'.$CatPage->get_HrefText($cat,$page).'</b><br />';
                $keyToCheck = FILE_START.$cat.":".$page.FILE_END;
                $in_description = '';
                $in_keywords = '';
                if($this->settings->keyExists($keyToCheck)) {
                    $setting = $this->settings->get($keyToCheck);
                    if(is_array($setting)) {
                        $in_description = $setting['description'];
                        $in_keywords = $setting['keywords'];
                    }
                }
                $html .= '<span>description:</span> <input class="admin-key-descr-in" name="keydescript['.$keyToCheck.'][description]" type="text" size="100" maxlength="255" value="'.$in_description.'" /><br />'
                .'<span>keywords:</span> <input name="keydescript['.$keyToCheck.'][keywords]" type="text" size="100" maxlength="255" value="'.$in_keywords.'" />'
                .'</li>';
            }
            $html .= '</ul></li>';
        }
        return $html.'</ul></div></form></div>';
    }
}
?>