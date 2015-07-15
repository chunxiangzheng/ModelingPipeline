from modeller import *
from modeller.automodel import *
import sys
import os
import math

if (len(sys.argv) < 2):
	return
log.verbose()
env = environ()

os.chdir(sys.argv[0])
#-- Prepare the input files

env = environ()
#-- Read in the sequence database
sdb = sequence_db(env)
path = sys.argv[0]
pro = sys.argv[1]
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

#--find best template
template = ""
with open("build_profile.ali", "r") as f:
    for line in f:
        if line.startswith("structure:"):
            arr = line.split(":")
            template = arr[1]
            break    
if template == "":
    raise Exception("No template available")
#--download pdb file
system("http://www.rcsb.org/pdb/files/" + template[0:4] + ".pdb")

#--align template with sequence
aln = alignment(env)
mdl = model(env, file=template[0:4], model_segment=('FIRST:' + template[-1],'LAST:' + template[-1]))
aln.append_model(mdl, align_codes=template, atom_files=template[0:4] + '.pdb')
aln.append(file=pro + '.ali', align_codes='TvLDH')
aln.align2d()
aln.write(file=pro + '-' + template + '.ali', alignment_format='PIR')
aln.write(file=pro + '-' + template + '.pap', alignment_format='PAP')

#from modeller import soap_protein_od

env = environ()
a = automodel(env, alnfile=pro + '-' + template + '.ali',
              knowns=template, sequence='TvLDH',
              assess_methods=(assess.DOPE,
                              #soap_protein_od.Scorer(),
                              assess.GA341))
a.starting_model = 1
#-- chanage here for how many models to output
a.ending_model = 5
a.make()

#--trim pdb
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

