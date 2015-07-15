from modeller import *

env = environ()
aln = alignment(env)
mdl = model(env, file='1OAP', model_segment=('FIRST:A','LAST:A'))
aln.append_model(mdl, align_codes='1oapA', atom_files='1OAP.pdb')
aln.append(file='B7I876.ali', align_codes='TvLDH')
aln.align2d()
aln.write(file='B7I876-1oapA.ali', alignment_format='PIR')
aln.write(file='B7I876-1oapA.pap', alignment_format='PAP')
