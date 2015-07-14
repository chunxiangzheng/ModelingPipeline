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
                $res = intval(substr($line, 22, 4));
                if (substr($line, 0, 4)==="ATOM" and $res===$residue) $atomNumber=substr($line, 6, 5);
            }
            fclose($pdb);
            $executeUpdate = $dbaccess->prepare("update xlinkdb 
                                                 set pdbA='".$job['proA']."' siteA='".$residue."' atomNumA='".$atomNumber."' 
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
                $res = intval(substr($line, 22, 4));
                if (substr($line, 0, 4)==="ATOM" and $res===$residue) $atomNumber=substr($line, 6, 5);
            }
            fclose($pdb);
            $executeUpdate = $dbaccess->prepare("update xlinkdb 
                                                 set pdbB='".$job['proA']."' siteB='".$residue."' atomNumB='".$atomNumber."' 
                                                 where `cross_linkID`=".$update['cross_linkID']);
            $executeUpdate->execute();
        }
    }
}
?>
