<?php

if($device['os_group'] == "unix")
{

  echo("Observium UNIX Agent: ");
  
  $port='6556';
  
  $agent = fsockopen($device['hostname'], $port, $errno, $errstr, 10);
  
  if(!$agent)
  {
    echo "Connection to UNIX agent failed on port ".$port.".";
  } else {
    while (!feof($agent))
    {
      $agent_raw .= fgets($agent, 128);
    }
  }
  
  if(!empty($agent_raw))
  {
    foreach (explode("<<<", $agent_raw) as $section)
    {
  
      list($section, $data) = explode(">>>", $section);
      $agent_data[$section] = trim($data);
    }
  
    ## FIXME - split these into separate modules which are "autoloaded" when the section exists.
  
    ### RPM
    if (!empty($agent_data['rpm']))
    {
      echo("\nRPM Packages: ");
      ## Build array of existing packages
      $manager = "rpm";
  
      $pkgs_db_db = dbFetchRows("SELECT * FROM `packages` WHERE `device_id` = ?", array($device['device_id']));
      foreach ($pkgs_db_db as $pkg_db)
      {
        $pkgs_db[$pkg_db['manager']][$pkg_db['name']][$pkg_db['arch']][$pkg_db['version']][$pkg_db['build']]['id'] = $pkg_db['pkg_id'];
        $pkgs_db[$pkg_db['manager']][$pkg_db['name']][$pkg_db['arch']][$pkg_db['version']][$pkg_db['build']]['status'] = $pkg_db['status'];
        $pkgs_db[$pkg_db['manager']][$pkg_db['name']][$pkg_db['arch']][$pkg_db['version']][$pkg_db['build']]['size'] = $pkg_db['size'];
        $pkgs_db_id[$pkg_db['pkg_id']]['text'] = $pkg_db['manager'] ."-".$pkg_db['name']."-".$pkg_db['arch']."-".$pkg_db['version']."-".$pkg_db['build'];
        $pkgs_db_id[$pkg_db['pkg_id']]['manager'] = $pkg_db['manager'];
        $pkgs_db_id[$pkg_db['pkg_id']]['name']    = $pkg_db['name'];
        $pkgs_db_id[$pkg_db['pkg_id']]['arch']    = $pkg_db['arch'];
        $pkgs_db_id[$pkg_db['pkg_id']]['version'] = $pkg_db['version'];
        $pkgs_db_id[$pkg_db['pkg_id']]['build']   = $pkg_db['build'];
      }
  
      foreach (explode("\n", $agent_data['rpm']) as $package)
      {
        list($name, $version, $build, $arch, $size) = explode(" ", $package);
        $pkgs[$manager][$name][$arch][$version][$build]['manager'] = $manager;
        $pkgs[$manager][$name][$arch][$version][$build]['name']    = $name;
        $pkgs[$manager][$name][$arch][$version][$build]['arch']    = $arch;
        $pkgs[$manager][$name][$arch][$version][$build]['version'] = $version;
        $pkgs[$manager][$name][$arch][$version][$build]['build']   = $build;
        $pkgs[$manager][$name][$arch][$version][$build]['size']    = $size;
        $pkgs[$manager][$name][$arch][$version][$build]['status']  = '1';
        $text = $manager."-".$name."-".$arch."-".$version."-".$build;
        $pkgs_id[] = $pkgs[$manager][$name][$arch][$version][$build];
      }
    }
  
    ### DPKG
    if (!empty($agent_data['dpkg']))
    {
      echo("\nDEB Packages: ");
      ## Build array of existing packages
      $manager = "deb";
  
      $pkgs_db_db = dbFetchRows("SELECT * FROM `packages` WHERE `device_id` = ?", array($device['device_id']));
      foreach ($pkgs_db_db as $pkg_db)
      {
        $pkgs_db[$pkg_db['manager']][$pkg_db['name']][$pkg_db['arch']][$pkg_db['version']][$pkg_db['build']]['id'] = $pkg_db['pkg_id'];
        $pkgs_db[$pkg_db['manager']][$pkg_db['name']][$pkg_db['arch']][$pkg_db['version']][$pkg_db['build']]['status'] = $pkg_db['status'];
        $pkgs_db[$pkg_db['manager']][$pkg_db['name']][$pkg_db['arch']][$pkg_db['version']][$pkg_db['build']]['size'] = $pkg_db['size'];
        $pkgs_db_id[$pkg_db['pkg_id']]['text'] = $pkg_db['manager'] ."-".$pkg_db['name']."-".$pkg_db['arch']."-".$pkg_db['version']."-".$pkg_db['build'];
        $pkgs_db_id[$pkg_db['pkg_id']]['manager'] = $pkg_db['manager'];
        $pkgs_db_id[$pkg_db['pkg_id']]['name']    = $pkg_db['name'];
        $pkgs_db_id[$pkg_db['pkg_id']]['arch']    = $pkg_db['arch'];
        $pkgs_db_id[$pkg_db['pkg_id']]['version'] = $pkg_db['version'];
        $pkgs_db_id[$pkg_db['pkg_id']]['build']   = $pkg_db['build'];
      }
  
      foreach (explode("\n", $agent_data['dpkg']) as $package)
      {
        list($name, $version, $arch, $size) = explode(" ", $package);
        $build = "";
        $pkgs[$manager][$name][$arch][$version][$build]['manager'] = $manager;
        $pkgs[$manager][$name][$arch][$version][$build]['name']    = $name;
        $pkgs[$manager][$name][$arch][$version][$build]['arch']    = $arch;
        $pkgs[$manager][$name][$arch][$version][$build]['version'] = $version;
        $pkgs[$manager][$name][$arch][$version][$build]['build']   = $build;
        $pkgs[$manager][$name][$arch][$version][$build]['size']    = $size;
        $pkgs[$manager][$name][$arch][$version][$build]['status']  = '1';
        $text = $manager."-".$name."-".$arch."-".$version."-".$build;
        $pkgs_id[] = $pkgs[$manager][$name][$arch][$version][$build];
      }
    }
  
    ## This is run for all "packages" and is common to RPM/DEB/etc
    foreach ($pkgs_id as $pkg)
    {
      $name    = $pkg['name'];
      $version = $pkg['version'];
      $build   = $pkg['build'];
      $arch    = $pkg['arch'];
      $size    = $pkg['size'];
  
      #echo(str_pad($name, 20)." ".str_pad($version, 10)." ".str_pad($build, 10)." ".$arch."\n");
      #echo($name." ");
  
      if (is_array($pkgs_db[$pkg['manager']][$pkg['name']][$pkg['arch']][$pkg['version']][$pkg['build']]))
      {
        ### FIXME - packages_history table
        $id = $pkgs_db[$pkg['manager']][$pkg['name']][$pkg['arch']][$pkg['version']][$pkg['build']]['id'];
        if ($pkgs_db[$pkg['manager']][$pkg['name']][$pkg['arch']][$pkg['version']][$pkg['build']]['status'] != '1')
        {
          $pkg_update['status']  = '1';
        }
        if ($pkgs_db[$pkg['manager']][$pkg['name']][$pkg['arch']][$pkg['version']][$pkg['build']]['size'] != $size)
        {
          $pkg_update['size']  = $size;
        }
        if (!empty($pkg_update))
        {
         dbUpdate($pkg_update, 'packages', '`pkg_id` = ?', array($id));
          echo("u");
        } else {
          echo(".");
        }
        unset($pkgs_db_id[$id]);
      } else {
        if (count($pkgs[$manager][$name][$arch], 1) > "10" || count($pkgs_db[$manager][$name][$arch], 1) == '0')
        {
          dbInsert(array('device_id' => $device['device_id'], 'name' => $name, 'manager' => $manager,
                       'status' => 1, 'version' => $version, 'build' => $build, 'arch' => $arch, 'size' => $size), 'packages');
          if ($build != "") { $dbuild = '-' . $build; } else { $dbuild = ''; }
          echo("+".$name."-".$version.$dbuild."-".$arch);
          log_event('Package installed: '.$name.' ('.$arch.') version '.$version.$dbuild, $device, 'package');
        } elseif(count($pkgs_db[$manager][$name][$arch], 1)) {
          $pkg_c = dbFetchRow("SELECT * FROM `packages` WHERE `device_id` = ? AND `manager` = ? AND `name` = ? and `arch` = ? ORDER BY version DESC, build DESC", array($device['device_id'], $manager, $name, $arch));
          if ($pkg_c['build'] != "") { $pkg_c_dbuild = '-'.$pkg_c['build']; } else { $pkg_c_dbuild = ''; }
          echo("U(".$pkg_c['name']."-".$pkg_c['version'].$pkg_c_dbuild."|".$name."-".$version.$dbuild.")");
          $pkg_update = array('version' => $version, 'build' => $build, 'status' => '1', 'size' => $size);
          dbUpdate($pkg_update, 'packages', '`pkg_id` = ?', array($pkg_c['pkg_id']));
          log_event('Package updated: '.$name.' ('.$arch.') from '.$pkg_c['version'].$pkg_c_dbuild .' to '.$version.$dbuild, $device, 'package');
          unset($pkgs_db_id[$pkg_c['pkg_id']]);
        }
      }
      unset($pkg_update);
    }
  
    ## Packages
    foreach ($pkgs_db_id as $id => $pkg)
    {
      dbDelete('packages', "`pkg_id` =  ?", array($id));
      echo("-".$pkg['text']);
      log_event('Package removed: '.$pkg['name'].' '.$pkg['arch'].' '.$pkg['version'].'-'.$pkg['build'], $device, 'package');
    }
  
    ### Processes
    if (!empty($agent_data['ps']))
    {
      echo("\nProcesses: ");
      foreach (explode("\n", $agent_data['ps']) as $process)
      {
        $process = preg_replace("/\((.*),([0-9]*),([0-9]*),([0-9\.]*)\)\ (.*)/", "\\1|\\2|\\3|\\4|\\5", $process);
        list($user, $vsz, $rss, $pcpu, $command) = explode("|", $process, 5);
  	$processlist[] = array('user' => $user, 'vsz' => $vsz, 'rss' => $rss, 'pcpu' => $pcpu, 'command' => $command);
      }
      #print_r($processlist);
    }
  
    ### Apache
    if (!empty($agent_data['apache']))
    {
      $app_found['apache'] = TRUE;
      if(dbFetchCell("SELECT COUNT(*) FROM `applications` WHERE `device_id` = ? AND `app_type` = ?", array($device['device_id'], 'apache')) == "0")
      {
        echo("Found new application 'Apache'\n");
        dbInsert(array('device_id' => $device['device_id'], 'app_type' => 'apache'), 'applications');
      }
    }
  
    ### MySQL
    if (!empty($agent_data['mysql']))
    {
      $app_found['mysql'] = TRUE;
      if(dbFetchCell("SELECT COUNT(*) FROM `applications` WHERE `device_id` = ? AND `app_type` = ?", array($device['device_id'], 'mysql')) == "0")
      {
        echo("Found new application 'MySQL'\n");
        dbInsert(array('device_id' => $device['device_id'], 'app_type' => 'mysql'), 'applications');
      }
    }
  
    ### DRBD
    if (!empty($agent_data['drbd']))
    {
      $agent_data['drbd_raw'] = $agent_data['drbd'];
      $agent_data['drbd'] = array();
      foreach (explode("\n", $agent_data['drbd_raw']) as $drbd_entry)
      {
        list($drbd_dev, $drbd_data) = explode(":", $drbd_entry);
        if(preg_match("/^drbd/", $drbd_dev))
        {
          $agent_data['drbd'][$drbd_dev] = $drbd_data;
          if(dbFetchCell("SELECT COUNT(*) FROM `applications` WHERE `device_id` = ? AND `app_type` = ? AND `app_instance` = ?", array($device['device_id'], 'drbd', $drbd_dev)) == "0")
          {
            echo("Found new application 'DRBd' $drbd_dev\n");
            dbInsert(array('device_id' => $device['device_id'], 'app_type' => 'drbd', 'app_instance' => $drbd_dev), 'applications');
          }
        }
      }
    }
  }
  
  unset($pkg);
  unset($pkgs_db_id);
  unset($pkg_c);
  unset($pkgs);
  unset($pkgs_db);
  unset($pkgs_db_db);
  
  echo("\n");
}

?>
