<?php
include_once("/var/www/html/xlinkdb/db/MyPDO.php");
$dbaccess = new MyPDO();
$jobs = $dbaccess->prepare("select * from jobdb where `name`='docking' and `status`='ongoing'");
$jobs->execute();
while($job=$jobs->fetch()){
    $folderPath = "/home/czheng/jenkins_workspace/".$job['proA']."_".$job['proB']."/";
    if (file_exists($folderPath.$job['proA']."_".$job['proB'].".pdb")) {
        $updateJob = $dbaccess->prepare("update jobdb set `status`='complete' where `job_id`=".$job['job_id']);
        $updateJob->execute();
        //move file
        system("cp ".$folderPath.$job['proA']."_".$job['proB'].".pdb /var/www/html/xlinkdb/pdb/");
        //update database
        $needUpdate = $dbaccess->prepare("select * from xlinkdb where proA='".$job['proA']."' and proB='".$job['proB']."'");
        $needUpdate->execute();
        while($update=$needUpdate->fetch()) {
            $residueA = intval($update['kposA']) + intval($update['startPosA']) + 1;
            $residueB = intval($update['kposB']) + intval($update['startPosB']) + 1;
            $pdb = fopen($folderPath.$job['proA']."_".$job['proB'].".pdb", "r");
            $pdbCode = $job['proA']."_".$job['proB'];
            $atomNumberA = "####";
            $atomNumberB = "####";
            $coodsA = array("x"=>0, "y"=>0, "z"=>0);
            $coodsB = array("x"=>0, "y"=>0, "z"=>0);
            while($line=fgets($pdb)) {
                $res = intval(substr($line, 22, 4));
                if (substr($line, 0, 4)==="ATOM" and $res===$residue and substr($line, 21, 1)==="A" and substr($line, 12, 2)==="CA") {
                    $atomNumberA=substr($line, 6, 5);
                    $coodsA['x']=intval($line, 30, 8);
                    $coodsA['y']=intval($line, 38, 8);
                    $coodsA['z']=intval($line, 46, 8);
                }
                if (substr($line, 0, 4)==="ATOM" and $res===$residue and substr($line, 21, 1)==="B" and substr($line, 12, 2)==="CA") {
                    $atomNumberB=substr($line, 6, 5);
                    $coodsB['x']=intval($line, 30, 8);
                    $coodsB['y']=intval($line, 38, 8);
                    $coodsB['z']=intval($line, 46, 8);
                }
            }
            fclose($pdb);
            $distance = "####";
            if (($coodsA['x']!=0 or $coodsA['y']!=0 or $coodsA['z']!=0) and ($coodsB['x']!=0 or $coodsB['y']!=0 or $coodsB['z']!=0)) {
                $distance = sqrt(pow($coodsA['x']-$coodsB['x'], 2) + pow($coodsA['y']-$coodsB['y'], 2) + pow($coodsA['z'] - $coodsB['z'], 2));
            }
            $executeUpdate = $dbaccess->prepare("update xlinkdb 
                                                 set pdbA='".$pdbCode."', siteA='".$residueA.":A', atomNumA='".$atomNumberA."', 
                                                     pdbB='".$pdbCode."', siteB='".$residueB.":B', atomNumB='".$atomNumberB."', distance=".$distance."   
                                                 where `cross_linkID`=".$update['cross_linkID']);
            $executeUpdate->execute();      
        }
        //repeat the code above for proB, proA reverse, it is bad practice but whatsoever
        $needUpdate = $dbaccess->prepare("select * from xlinkdb where proB='".$job['proA']."' and proA='".$job['proB']."'");
        $needUpdate->execute();
        while($update=$needUpdate->fetch()) {
            $residueA = intval($update['kposB']) + intval($update['startPosB']) + 1;
            $residueB = intval($update['kposA']) + intval($update['startPosA']) + 1;
            $pdb = fopen($folderPath.$job['proA']."_".$job['proB'].".pdb", "r");
            $pdbCode = $job['proA']."_".$job['proB'];
            $atomNumberA = "####";
            $atomNumberB = "####";
            $coodsA = array("x"=>0, "y"=>0, "z"=>0);
            $coodsB = array("x"=>0, "y"=>0, "z"=>0);
            while($line=fgets($pdb)) {
                $res = intval(substr($line, 22, 4));
                if (substr($line, 0, 4)==="ATOM" and $res===$residue and substr($line, 21, 1)==="A" and substr($line, 12, 2)==="CA") {
                    $atomNumberA=substr($line, 6, 5);
                    $coodsA['x']=intval($line, 30, 8);
                    $coodsA['y']=intval($line, 38, 8);
                    $coodsA['z']=intval($line, 46, 8);
                }
                if (substr($line, 0, 4)==="ATOM" and $res===$residue and substr($line, 21, 1)==="B" and substr($line, 12, 2)==="CA") {
                    $atomNumberB=substr($line, 6, 5);
                    $coodsB['x']=intval($line, 30, 8);
                    $coodsB['y']=intval($line, 38, 8);
                    $coodsB['z']=intval($line, 46, 8);
                }
            }
            fclose($pdb);
            $distance = "####";
            if (($coodsA['x']!=0 or $coodsA['y']!=0 or $coodsA['z']!=0) and ($coodsB['x']!=0 or $coodsB['y']!=0 or $coodsB['z']!=0)) {
                $distance = sqrt(pow($coodsA['x']-$coodsB['x'], 2) + pow($coodsA['y']-$coodsB['y'], 2) + pow($coodsA['z'] - $coodsB['z'], 2));
            }
            $executeUpdate = $dbaccess->prepare("update xlinkdb 
                                                 set pdbA='".$pdbCode."', siteA='".$residueB.":B', atomNumA='".$atomNumberB."', 
                                                     pdbB='".$pdbCode."', siteB='".$residueA.":A', atomNumB='".$atomNumberA."', distance=".$distance."   
                                                 where `cross_linkID`=".$update['cross_linkID']);
            $executeUpdate->execute();      
        }
        
    }
}
?>
