<?php
include_once("/var/www/html/xlinkdb/db/MyPDO.php");
$dbaccess = new MyPDO();
//get protein list for modeling
$result = $dbaccess->prepare("select distinct * 
                              from 
                                  (select proA as proid, seqA as proseq 
                                   from xlinkdb 
                                   where `pdbA`='####'
                                   union all
                                   select proB as proid, seqB as proseq
                                   from xlinkdb
                                   where `pdbB`='####')");
$result->execute();
while($row=$result->fetch()) {
   $proid = $row['proid'];
   $folderPath = "/home/czheng/jenkins_workspace/".$proid;
   if (file_exists($folderPath) continue;
   mkdir($folderPath);
   //update status to waiting
   $insert = $dbaccess->prepare("insert into jobdb (name, status, proA) values('model','waiting',".$proid.")");
   $insert->execute();
   $jobid_query = $dbaccess->prepare("select max(job_id) from jobdb");
   $jobid_query->execute();
   $jobid = $jobid_query->fetch();
   //create ali file
   $f_ali = fopen($folderPath."/".$proid."ali", "w");
   $header = ">P1;".$proid."\n";
   $annotation = "sequence:".$proid.":::::::0.00:0.00\n";
   $seq = $row['proseq']."*";
   fwrite($f_ali, $header);
   fwrite($f_ali, $annotation);
   fwrite($f_ali, $seq);
   fclose($f_ali);
   //get distance constraint file
   $intra = $dbaccess->prepare("select distinct kposA, startPosA, kposB, startPosB from xlinkdb where `proA`='".$proid."' and `proB`='".$proid."'");
   $intra->execute();
   $distance = fopen($folderPath."/distance", "w");
   while ($data=$intra->fetch()) {
       fwrite(intval($data['kposA']) + intval($data['startPosA']) + 1));
       fwrite("\t");
       fwrite(intval($data['kposB']) + intval($data['startPosB']) + 1));
       fwrite("\n");
   }
   fclose($distance);   
   //push job
   system("gsub submitSingle.sh ".$folderPath." ".$proid." ".$jobid);
}
?>
