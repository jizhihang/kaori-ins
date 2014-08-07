# Written by Duy Le - ledduy@ieee.org
# Last update Jun 26, 2012
#!/bin/sh
# Force to use shell sh. Note that #$ is SGE command
#$ -S /bin/sh
# Force to limit hosts running jobs
#$ -q all.q@@bc3hosts,all.q@@bc4hosts
# Log starting time
date 
# include your library here
export LD_LIBRARY_PATH=/net/per610a/export/das09f/satoh-lab/minhduc/dependencies/local/python/2.6/lib/:/net/per610a/export/das09f/satoh-lab/minhduc/dependencies/local/opencv/2.0/lib/:$LD_LIBRARY_PATH
# display your command here
echo [$HOSTNAME] [$JOB_ID] [matlab -nodisplay -r "trecvid_test_2013_new( '$1', '$2', '$3', '$4')"]
# change to your code directory here
cd /net/per610a/export/das09f/satoh-lab/minhduc/resources/object_Detection/voc-release5
# Log info of current dir
pwd
# run your command with parameters ($1, $2,...) here, string variable is put in ' '
matlab -nodisplay -r "startup;trecvid_test_2013_new_supp( '$1', '$2', '$3', '$4'); quit;"
# Log ending time
date
