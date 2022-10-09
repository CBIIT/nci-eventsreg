<?php

namespace Drupal\eventsreg\Utility;

use Drupal\node\Entity\Node;
/**
 * Provides helper to setup logo, menus, for EventsReg Website.
 */
class EventsRegPageMaker {

  public const LAST_NID_FOR_SHORT_URL = '546';  //Look up in node table in production
  //public const LAST_NID_FOR_SHORT_URL = '452';  //Look up in node table

  public static function setupPage(&$variables) {
    self::setupMenus($variables); 
    //dump($variables['eventsreg']['menus']);
  }
  
  public static function setupMenus(&$variables) {

    $route_name = \Drupal::routeMatch()->getRouteName();
    //dump("Route Name: ".$route_name);
    //dump($variables);
    if($route_name == 'entity.node.canonical') {
      $node = \Drupal::routeMatch()->getParameter('node');
      //dump($node);
      $nid = $node->id();
      //$bundle = $node->getType();
      //$variables['route_name'] = \Drupal::routeMatch()->getRouteName();
      //drupal_set_title('Wow.  I got the title');
      //drupal_set_message($route);
      //dpm("setupPage: nid: ". $nid);
      //$node = Node::load($nid);
      //$bundle = $node->getType();

      $path = \Drupal::service('path_alias.manager')->getAliasByPath('/node/'.$nid);
      $event_path = self::formatHomeLink($path);
      $event_nid = self::getEventNid($event_path);  //One simple SQL
      $acronym = self::getAcronymFromPath($path);

      $variables['eventsreg']['active_link'] = $path;
      $variables['eventsreg']['home_link'] = $event_path;
      $variables['eventsreg']['event_nid'] = $event_nid;
      $variables['eventsreg']['acronym'] = $acronym;
      //dump($variables['eventsreg']);

      //drupal_set_message("OFFICIAL PATH: ".$path);

      //$event_path = self::getEventPath($path);

      //drupal_set_message("OFFICIAL PATH: ".$path);
      $event_nid = self::getEventNodeIdFromPath($path);

      if($event_nid > 0) {
        //drupal_set_message("THIS IS AN OFFICIAL WEBSITE");
        if($event_nid > self::LAST_NID_FOR_SHORT_URL) {
          $menus = ["menu" => $event_nid."-menu", "util" => $event_nid."-util", "admin" => $event_nid."-admin"];
        } else {
          $menus = ["menu" => $acronym."-menu", "util" => $acronym."-util", "admin" => $acronym."-admin"];
        }
      } else {
        //drupal_set_message("THIS IS AN Not a website!");
        #$menus = ["menu" => "default-menu", "util" => "ccr-traco-menu", "admin" => "default-admin"];
        $menus = ["menu" => "default-menu", "util" => "default-util", "admin" => "default-admin"];
      }
      //dump("Here are the menus");
      //dump($menus);

    } else {
      // Set Default menus
      $menus = ["menu" => "default-menu", "util" => "default-util", "admin" => "default-admin"];
    }

    foreach($menus as $menu => $menu_name) {
      if(isMenu($menu_name)) {
        self::attachMenuTree($menu, $menu_name, $variables);
      }
    }

    return;
  }
  
/*
  private static function getAcronymFromWebformNode($nid) {
    $acronym = "";

    $query = "SELECT webform_target_id FROM node__webform where entity_id = $nid";
    $result = \Drupal::database()->query($query);
    $row = $result->fetchObject();
    $webform_id = $row->webform_target_id;
    $pieces = explode("_", $webform_id);
    $acronym = $pieces[0].'-'.$pieces[1];

    return $acronym;
  }
*/
/*
  private static function isWebsite($acronym) {
    $query = "SELECT count(*) as isWebsite FROM node_field_data ";
    $query .= "where type = 'event' and title='{$acronym}'; ";
    dump($query);
    $result = \Drupal::database()->query($query);
    $row = $result->fetchObject();
    //$webform_id = $row->webform_target_id;

    return $row->isWebsite;
  }
*/

  private static function getEventNid($event_path) {
    $query = "SELECT entity_id as nid FROM node__field_event_home_link ";
    $query .= "where field_event_home_link_uri = 'internal:{$event_path}'; ";
    //dump($query);
    $result = \Drupal::database()->query($query);
    $row = $result->fetchObject();
    //$webform_id = $row->webform_target_id;

    return (isset($row->nid) ? $row->nid : 0);
  }
  
  private static function getEventPath($path){
    $pieces = explode("/", substr($path, 1));
    $event_path = "unknown";
    if(isset($pieces[1])){
      $event_path =$pieces[0].'/'.$pieces[1]; 
    }
    return $event_path;
    //return str_replace("/", "-", substr($alias, 1));
  }

  private static function getAcronymFromPath($alias){
    $pieces = explode("/", substr($alias, 1));
    $acronym = "unknown";
    if(isset($pieces[1])){
      //dump("decodeAlias: alias is ".$alias." retrived from path");
      $acronym =$pieces[0].'-'.$pieces[1]; 
    }
    
    return $acronym;
    //return str_replace("/", "-", substr($alias, 1));
  }

  private static function getEventNodeIdFromPath($path){
    
    // Let's Look up this path in the node table to see where it is going.

    $pieces = explode("/", substr($path, 1));
    $nid = "unknown";
    $event_nid = 0;
    if(isset($pieces[1])){
      //dump("path is ".$path);

      //$event_path equals the first two pieces of the path.
      $event_path =$pieces[0].'/'.$pieces[1]; 
      //dump("event_path: is ".$event_path." the first two pieces of the path");

      $query = "SELECT nid FROM node_field_data nfd, node__field_event_home_link nfehl ";
      $query .= "where nfd.type='event' ";
      $query .= "and nfehl.entity_id = nfd.nid ";
      $query .= "and nfehl.field_event_home_link_uri = 'internal:/{$event_path}';";
      //dump($query);
      $result = \Drupal::database()->query($query);
      $row = $result->fetchObject();
      //$webform_id = $row->webform_target_id;
      $event_nid = (isset($row->nid) ? $row->nid : 0);
      //dump("event_nid: ".$event_nid);
    }

    return $event_nid;
 
  }

  private static function attachMenuTree($menu, $menu_name, &$variables) {
    //dump($menu." : ">$menu_name);
    $variables['eventsreg']['menus'][$menu] = self::menuLoadLinks($menu_name);
    //dump($variables);
    //ksm($variables);
    return;
  }

  private static function menuLoadLinks($menu_name) {
    $links = [];
    $storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
    $menu_links = $storage->loadByProperties(['menu_name' => $menu_name]);
    //kint($menu_links);
    if (empty($menu_links)) return $links;
    foreach ($menu_links as $mlid => $menu_link) {
      //dpm("link_enabled".$menu_link->enabled);
      //dump($menu_link->title->value);
      //dump($menu_link->enabled->value);
      if($menu_link->enabled->value) {
        $link = [];
        $link['type'] = 'menu_link';
        $link['mlid'] = $menu_link->id->value;
        $link['plid'] = $menu_link->parent->value ?? '0';
        $link['menu_name'] = $menu_link->menu_name->value;
        $link['link_title'] = $menu_link->title->value;
        $link['uri'] = $menu_link->link->uri;
        $link['link'] = self::formatLink($menu_link->link->uri);
        $link['alias'] = self::nodeAlias($menu_link->link->uri);
        $link['options'] = $menu_link->link->options;
        $link['weight'] = $menu_link->weight->value;
        $links[] = $link;
      }
    }
    // Sort menu links by weight element
    //usort($links, [SortArray::class, 'weight']);
    //$sorted = array_orderby($links, 'weight', SORT_DESC);
    //dump($links);
    $sortArray = array();

    foreach($links as $mylinks){
        foreach($mylinks as $key=>$value){
            if(!isset($sortArray[$key])){
                $sortArray[$key] = array();
            }
            $sortArray[$key][] = $value;
        }
    } 
    $orderby = "weight";

    array_multisort($sortArray[$orderby], SORT_ASC, $links);

    //dump($links);

    return $links;
  }

  private static function nodeAlias($uri) {

    $pieces = explode(":", $uri);
    $link = $pieces[1];
    if($pieces[0] == 'entity') {
      $source = "/".$pieces[1];
      $query = "SELECT alias FROM path_alias where path = '{$source}'";
      $result = \Drupal::database()->query($query);
      $row = $result->fetchObject();
      $alias = $row->alias;
      $link = $alias;
    }
    if($pieces[0] == 'http' || $pieces[0] == 'https') {
      $link = $uri;      
    }

    return $link;

  }

  private static function formatLink($uri) {
    $pieces = explode(":", $uri);
    //
    //dump($pieces);
    $link = $pieces[1];
    if($pieces[0] == 'entity') {
      $link = "/".$pieces[1];
    }
    return $link;
  }

  private static function formatHomeLink($path) {
    //Home Link will always be the frist two varialbes of the $current_path

    $pieces = explode("/", $path);
    $link = "/";
    if(isset($pieces[1])) {
      $link = "/".$pieces[1];
    }
    if(isset($pieces[2])) {
      $link = "/".$pieces[1]."/".$pieces[2];
    }
    //dump("link: $link");
    return $link;
  }

}
