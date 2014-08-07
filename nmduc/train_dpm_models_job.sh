# Written by Duy Le - ledduy@ieee.org
# Last update Jun 26, 2012
#!/bin/sh
# Force to use shell sh. Note that #$ is SGE command
#$ -S /bin/sh
# Force to limit hosts running jobs
#$ -q all.q@bc501.hpc.vpl.nii.ac.jp,all.q@bc502.hpc.vpl.nii.ac.jp,all.q@bc503.hpc.vpl.nii.ac.jp
# Log starting time
date 
# include your library here
export LD_LIBRARY_PATH=/net/per610a/export/das09f/satoh-lab/minhduc/dependencies/local/python/2.6/lib/:/net/per610a/export/das09f/satoh-lab/minhduc/dependencies/local/opencv/2.0/lib/:$LD_LIBRARY_PATH
# display your command here
echo [$HOSTNAME] [$JOB_ID] [matlab -nodisplay -r "train_model( 'query_$1' )"]
# change to your code directory here
cd /net/per900c/raid0/ledduy/github-projects/kaori-ins2014/voc-release5
# Log info of current dir
pwd
# run your command with parameters ($1, $2,...) here, string variable is put in ' '
matlab -nodisplay -r "addpath('trecvid_config_overide/'); global VOC_CONFIG_OVERRIDE; VOC_CONFIG_OVERRIDE = @voc_config_$1; startup; train_model( 'query_$1', 1);"
# Log ending time
date
