from modeller import *
import sys
import os

if (len(sys.argv) < 2):
	return
log.verbose()
env = environ()

os.chdir(sys.argv[0])
#-- Prepare the input files

#-- Read in the sequence database
sdb = sequence_db(env)

#-- Now, read in the binary database
sdb.read(seq_database_file='pdb_95.bin', seq_database_format='BINARY',
	 chains_list='ALL')

#-- Read in the target sequence/alignment
aln = alignment(env)
aln.append(file=sys.argv[1] + '.ali', alignment_format='PIR', align_codes='ALL')

#-- Convert the input sequence/alignment into
#   profile format
prf = aln.to_profile()

#-- Scan sequence database to pick up homologous sequences
prf.build(sdb, matrix_offset=-450, rr_file='${LIB}/blosum62.sim.mat',
	  gap_penalties_1d=(-500, -50), n_prof_iterations=1,
	  check_profile=False, max_aln_evalue=0.01)

#-- Write out the profile in text format
prf.write(file='build_profile.prf', profile_format='TEXT')

#-- Convert the profile back to alignment format
aln = prf.to_alignment()

#-- Write out the alignment file
aln.write(file='build_profile.ali', alignment_format='PIR')


from modeller import *

env = environ()
aln = alignment(env)
mdl = model(env, file='1OAP', model_segment=('FIRST:A','LAST:A'))
aln.append_model(mdl, align_codes='1oapA', atom_files='1OAP.pdb')
aln.append(file='B7I876.ali', align_codes='TvLDH')
aln.align2d()
aln.write(file='B7I876-1oapA.ali', alignment_format='PIR')
aln.write(file='B7I876-1oapA.pap', alignment_format='PAP')
from modeller import *
from modeller.automodel import *
#from modeller import soap_protein_od

env = environ()
a = automodel(env, alnfile='B7I876-1oapA.ali',
              knowns='1oapA', sequence='TvLDH',
              assess_methods=(assess.DOPE,
                              #soap_protein_od.Scorer(),
                              assess.GA341))
a.starting_model = 1
a.ending_model = 5
a.make()
import os
import math
def trimPDB(pdb, start, end):
	f = open(pdb, "r")
	fout=open(pdb + ".trim", "w")
	for line in f.read().split("\n"):
		if line.startswith("ATOM  "): 
			resNum = int(line[22:26])
			if resNum >= start and resNum <= end:
				fout.write(line[0:21] + "A" + line[22:] + "\n")
		else:
			fout.write(line + "\n")	
	fout.close()
	f.close()
def changeChainToB(pdb, pdbout):
	f = open(pdb, "r")
	fout = open(pdbout, "w")
	for line in f.read().split("\n"):
		if line.startswith("ATOM  "):
			fout.write(line[0:21] + "B" + line[22:] + "\n")
		else:
			fout.write(line + "\n")	
	fout.close()
	f.close()
prefix="TvLDH.B9999000"
if False:
	for i in range(1, 6):
		trimPDB(prefix + str(i) + ".pdb", 218, 341)

	for i in range(1, 6):
		folder = "ompA_dimer_" + str(i)	
		#os.system("mkdir " + folder)
		pdb = prefix + str(i) + ".pdb"
		os.system("cp " + pdb + " " + folder + "/a.pdb")
		changeChainToB(pdb, folder + "/b.pdb")
		os.system("cp distanceConstraint " + folder)

#run IMP
if False:
	fdir = "ompA_dimer"
	os.chdir(fdir)
	for root, dirs, files in os.walk(".") :
		for subdir in dirs :
			os.chdir(subdir)
			os.system("idock.py --cxms=distanceConstraint --patch_dock=/home/czheng/PatchDock --precision=1 a.pdb b.pdb")
			os.chdir("..")
#get docking output
def output(fdir, outputDir):
	for subdir in os.listdir(fdir) :
		os.chdir(fdir + "/" + subdir)
		os.system("/home/czheng/PatchDock/transOutput.pl results_cxms_soap.txt 1 100")
		for i in range(1, 101):
			os.system("mv results_cxms_soap.txt." + str(i) + ".pdb ../../" + outputDir + "/" + subdir + "_" + str(i) + ".pdb")
		os.chdir("../../")
#generate output folder
outputDir = "output"
if not os.path.exists(outputDir) :
	 os.system("mkdir " + outputDir)
######################

#output("ompA_dimer", "output")

##################### Scoring ##############################

f = open("distanceConstraint.list", "r")
pairs = set()
for line in f.read().split("\n"):
	arr = line.split("\t")
	if len(arr) < 2: continue
	if arr[0] < arr[1]: 
		pair = arr[0] + "\t" + arr[1]
	else:
		pair = arr[1] + "\t" + arr[0]
	pairs.add(pair)
f.close()
####################coords is a dictionary keeps residue number and chainID as key, and coordinates in a tuple as value#######################################
def calcDistance(coords, resA, resB):
	return math.sqrt((coords[resA][0] - coords[resB][0]) ** 2 + (coords[resA][1] - coords[resB][1]) ** 2 + (coords[resA][2] - coords[resB][2]) ** 2)
def getCoords(pdb):
	coords = dict()
	f = open(pdb, "r")
	for line in f.read().split("\n"):
		if line.startswith("ATOM  "):
			if line[12:16].strip() == "CA":
				code = line[22:26].strip() + "_" + line[21 : 22]
				x = float(line[30:38].strip())
				y = float(line[38:46].strip())
				z = float(line[46:54].strip())
				coords[code] = (x, y, z)
	f.close()
	return coords
outputFile = "ompA_dimer_docking.score"
fout = open(outputFile, "w")
scores = []
for pdb in os.listdir(outputDir):
	coords = getCoords(outputDir + "/" + pdb)
	score = 0
	for pair in pairs:
		distances = []
		arr = pair.split("\t")
		tmp = abs(calcDistance(coords, arr[0] + "_A", arr[1] + "_B") - 17)
		if tmp != 0: distances.append(tmp)
		tmp = abs(calcDistance(coords, arr[0] + "_B", arr[1] + "_A") - 17)
		if tmp != 0: distances.append(tmp)
		tmp = abs(calcDistance(coords, arr[0] + "_A", arr[1] + "_A") - 17)
		if tmp != 0: distances.append(tmp)
		tmp = abs(calcDistance(coords, arr[0] + "_B", arr[1] + "_B") - 17)
		if tmp != 0: distances.append(tmp)
		score += min(distances)
	scores.append((pdb, score))
def getKey(item):
	return item[1]
scores = sorted(scores, key=getKey)
for score in scores:
	fout.write(score[0] + "\t" + str(score[1]) + "\n")
fout.close()

