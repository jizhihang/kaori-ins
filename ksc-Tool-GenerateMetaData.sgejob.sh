# Written by Duy Le - ledduy@ieee.org
# Last update Aug 11, 2013

#!/bin/sh

# Force to use shell sh. Note that #$ is SGE command
#$ -S /bin/sh

# Force to limit hosts running jobs
#$ -q all.q@bc501.hpc.vpl.nii.ac.jp,all.q@bc502.hpc.vpl.nii.ac.jp,all.q@bc503.hpc.vpl.nii.ac.jp,all.q@bc504.hpc.vpl.nii.ac.jp

# Log starting time
date 

# for opencv shared lib
#export LD_LIBRARY_PATH=/net/per900b/raid0/ledduy/usr.local/lib:$LD_LIBRARY_PATH

# Log info of the job to output file  --- *** CHANGED ***
echo [$HOSTNAME] [$JOB_ID] [ksc-Tool-GenerateMetaData] [$1] [$2] [$3] [$4]

# change to the code dir  --> NEW!!!  *** CHANGED ***
cd /net/per900b/raid0/ledduy/github-projects/kaori-ins

# Log info of current dir
pwd

# Command -  *** CHANGED ***
/net/per900b/raid0/ledduy/usr.local/bin/php -f ksc-Tool-GenerateMetaData.php $1 $2 $3 $4

# Log ending time
date
