#$ -S /bin/sh
#$ -q all.q@@bc3hosts,all.q@@bc4hosts
matlab -r "addpath /net/per610a/export/das11f/ledduy/ndthanh/InstanceSearch2013/code/matlab; ins13BowEncoding('$1',$2,$3); quit;" -nojvm -nodisplay