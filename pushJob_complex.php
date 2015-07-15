<?php
include_once("/var/www/html/xlinkdb/db/MyPDO.php");
$dbaccess = new MyPDO();
//get protein list for modeling
$result = $dbaccess->prepare("select distinct proA, proB, pdbA, pdbB from xlinkdb where `pdbA`!='####' and `pdbB`!='####' and `siteA`='####' and `siteB`='####'"); 
$result->execute();
while($row=$result->fetch()) {
   if ($row['proA'] < $row['proB']) {
       $proA = $row['proA'];
       $proB = $row['proB'];
   } else {
       $proA = $row['proB'];
       $proB = $row['proA'];
   }
   $folderPath = "/home/czheng/jenkins_workspace/".$folderName;
   if (file_exists($folderPath) continue;
   mkdir($folderPath);
   //update status to waiting
   $insert = $dbaccess->prepare("insert into jobdb (name, status, proA, proB) values('docking','waiting',".$proA.",".$proB.")");
   $insert->execute();
   $jobid_query = $dbaccess->prepare('select max(job_id) from jobdb');
   $jobid_query->execute();
   $jobid = $jobid_query->fetch();
   //cp pdb files
   system("cp /var/www/html/xlinkdb/pdb/".$proA.".pdb ".$folderPath."/a.pdb");
   system("cp /var/www/html/xlinkdb/pdb/".$proB.".pdb ".$folderPath."/b.pdb");
   //generate distanceConstraint file
   $distance = fopen($folderPath."/distanceConstraint", "w");
   $pairs_ab = $dbaccess->prepare("select distinct siteA, siteB from xlinkdb where `proA`='".$proA."' `proB`='".$proB."'");
   $pairs_ab->execute();
   while ($pair=$pairs_ab->fetch()){
       fwrite($distance, $pair['siteA']."\t".$pair['siteB']."\n");
   }
   $pairs_ba = $dbaccess->prepare("select distinct siteA, siteB from xlinkdb where `proB`='".$proA."' `proA`='".$proB."'");
   $pairs_ba->execute();
   while ($pair=$pairs_ba->fetch()) {
       fwrite($distance, $pair['siteB']."\t".$pair['siteA']."\n");
   }
   fclose($distance);
   //push job
   system("gsub submitComplex.sh ".$folderPath." ".$folderName." ".$jobid);
}
?>
