<?php
include_once("/var/www/html/xlinkdb/db/MyPDO.php");
$dbaccess = new MyPDO();
$jobs = $dbaccess->prepare("select * from jobdb where `name`='model' and `status`='ongoing'");
$jobs->execute();
while($job=$jobs->fetch()){
    $folderPath = "/home/czheng/jenkins_workspace/".$job['proA'];
    if (file_exists($folderPath.$job['proA'].".pdb")) {
        $updateJob = $dbaccess->prepare("update jobdb set `status`='complete' where `job_id`=".$job['job_id']);
        $updateJob->execute();
        //move file
        system("cp ".$folderPath.$job['proA'].".pdb /var/www/html/xlinkdb/pdb/");
        //update database
        $needUpdate = $dbaccess->prepare("select cross_linkID, kposA, startPosA from xlinkdb where proA='".$job['proA']."'");
        $needUpdate->execute();
        while($update=$needUpdate->fetch()) {
            $residue = intval($update['kposA']) + intval($update['startPosA']) + 1;
            $pdb = fopen($folderPath.$job['proA'].".pdb", "r");
            $atomNumber = "####";
            while($line=fgets($pdb)) {
                if (strlen($line) < 54) continue;
                $res = intval(substr($line, 22, 4));
                if (substr($line, 0, 4)==="ATOM" and $res===$residue and substr($line, 12, 2)==="CA") $atomNumber=substr($line, 6, 5);
            }
            fclose($pdb);
            $executeUpdate = $dbaccess->prepare("update xlinkdb 
                                                 set pdbA='".$job['proA']."' siteA='".$residue.":A' atomNumA='".$atomNumber."' 
                                                 where `cross_linkID`=".$update['cross_linkID']);
            $executeUpdate->execute();      
        }
        //repeat the code above for proB
        $needUpdate = $dbaccess->prepare("select cross_linkID, kposB, startPosB from xlinkdb where proB='".$job['proA']."'");
        $needUpdate->execute();
        while($update=$needUpdate->fetch()) {
            $residue = intval($update['kposB']) + intval($update['startPosB']) + 1;
            $pdb = fopen($folderPath.$job['proA'].".pdb", "r");
            $atomNumber = "####";
            while($line=fgets($pdb)) {
                if (strlen($line) < 54) continue;
                $res = intval(substr($line, 22, 4));
                if (substr($line, 0, 4)==="ATOM" and $res===$residue and substr($line, 12, 2)==="CA") $atomNumber=substr($line, 6, 5);
            }
            fclose($pdb);
            $executeUpdate = $dbaccess->prepare("update xlinkdb 
                                                 set pdbB='".$job['proA']."' siteB='".$residue.":A' atomNumB='".$atomNumber."' 
                                                 where `cross_linkID`=".$update['cross_linkID']);
            $executeUpdate->execute();
        }
        //update distances
        $distanceUpdate = $dbaccess->prepare("select * from xlinkdb where proA='".$job['proA']."' and proB='".$job['proB']."'");
        $distanceUpdate->execute();
        while($update=$distanceUpdate->fetch()) {
           if ($update['siteA']===$update['siteB']) continue;
           $residueA = intval($update['kposA']) + intval($update['startPosA']) + 1;
           $residueB = intval($update['kposB']) + intval($update['startPosB']) + 1;
           $pdb = fopen($folderPath.$job['proA'].".pdb", "r");
           $coodsA = array('x'=>0, 'y'=>0, 'z'=>0);
           $coodsB = array('x'=>0, 'y'=>0, 'z'=>0);
           while($line=fgets($pdb)){
               if (strlen($line) < 54) continue;
               $res = intval(substr($line, 22, 4));
               if (substr($line, 0, 4)==="ATOM" and $res===$residueA and substr($line, 12, 2)==="CA") {
                   $coordsA['x'] = intval(substr($line, 30, 8));
                   $coordsA['y'] = intval(substr($line, 38, 8));
                   $coordsA['z'] = intval(substr($line, 46, 8));
               }
               if (substr($line, 0, 4)==="ATOM" and $res===$residueB and substr($line, 12, 2)==="CA") {
                   $coordsB['x'] = intval(substr($line, 30, 8));
                   $coordsB['y'] = intval(substr($line, 38, 8));
                   $coordsB['z'] = intval(substr($line, 46, 8));
               }
           }
           if (($coordsA['x'] != 0 or $coordsA['y']!=0 or $coordsA['z']!=0) and ($coordsB['x']!=0 or $coordsB['y']!=0 or $coordsB['z']!=0)){
               $distance = sqrt(pow($coordsA['x'] - $coordsB['x'], 2) + pow($coordsA['y'] - $coordsB['y'], 2) + pow($coordsA['z'] - $coordsB['z'], 2));
               $tmpQuery=$dbaccess->prepare("update xlinkdb set distance=".$distance." where `cross_linkID`=".$update['cross_linkID']);
               $tmpQuery->execute();
           }
        }
    }
}
?>
