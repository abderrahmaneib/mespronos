<?php

/**
 * @file
 * Contains \Drupal\mespronos\Controller\LeagueController.
 */

namespace Drupal\mespronos\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\mespronos\Entity\League;
use Drupal\mespronos\Entity\RankingLeague;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Class LeagueController.
 *
 * @package Drupal\mespronos\Controller
 */
class LeagueController extends ControllerBase {

  public function index(League $league) {
    $last_bets_controller = new LastBetsController();
    $next_bets_controller = new NextBetsController();
    $last_bets = $last_bets_controller->lastBets($league,100);
    $next_bets = $next_bets_controller->nextBets($league,100);
    $ranking = RankingController::getRankingLeague($league);
    return [
      '#theme' =>'league-details',
      '#last_bets' => $last_bets,
      '#next_bets' => $next_bets,
      '#ranking' => $ranking,
      '#cache' => [
        'contexts' => ['user'],
        'tags' => [ 'user:'.\Drupal::currentUser()->id(),'league:'.$league->id()],
      ],
    ];
  }
  public function indexTitle(League $league) {
    return $league->label();
  }

  public function leaguesList() {
    $user = User::load(\Drupal::currentUser()->id());
    $leagues_as_status = self::getLeagueSortedFromStatus();
    $leagues_table = [];
    foreach ($leagues_as_status as $status => $leagues) {
      $leagues_table[$status] = [
        '#theme' => 'table',
        '#rows' => self::leaguesListParseLeagues($leagues,$user),
        '#header' => self::leaguesListGetHeader(),
        '#footer' => self::leaguesListGetFooter(),
        '#cache' => [
          'contexts' => ['user'],
          'tags' => [ 'leagues','user:'.$user->id()],
        ],
      ];
    }
    return [
      '#theme' =>'leagues-list',
      '#leagues' => $leagues_table,
    ];

  }

  /**
   * @return array
   */
  public static function getLeagueSortedFromStatus() {
    $leagues = League::loadMultiple();
    $return_leagues = [];
    foreach ($leagues as $league) {
      if(!isset($return_leagues[$league->getStatus(true)])) {
        $return_leagues[$league->getStatus(true)] = [];
      }
      $return_leagues[$league->getStatus(true)][] = $league;
    }
    return $return_leagues;
  }

  public static function leaguesListGetHeader() {
    return [
      t('Sport',array(),array('context'=>'mespronos-block')),
      t('Name',array(),array('context'=>'mespronos-block')),
      t('Days',array(),array('context'=>'mespronos-block')),
      t('Rank',array(),array('context'=>'mespronos-block')),
    ];
  }

  public static function leaguesListGetFooter() {
    return [];
  }

  /**
   * @param League[] $leagues
   * @param \Drupal\user\Entity\User $user
   * @return array
   */
  public static function leaguesListParseLeagues($leagues,User $user) {
    $rows = [];
    foreach ($leagues as $league) {

      $ranking = RankingLeague::getRankingForBetter($user,$league);
      $league_name = Link::fromTextAndUrl(
        $league->label(),
        Url::fromRoute('mespronos.league.index',['league'=>$league->id()])
      );
      $row = [
        'data' => [
          'sport' => $league->getSport()->label(),
          'names' => $league_name,
          'days' => $league->getDaysNumber(),
          'rank' => $user->id() > 0 && $ranking ? $ranking->getPosition() : '/',
        ]
      ];
      $rows[] = $row;
    }
    return $rows;
  }


}
