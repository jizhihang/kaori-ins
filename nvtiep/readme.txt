This if for TiepNV code!

Steps to run:

1. Extract features
extract_hesaffine_rootsift_noangle.m
extract_perdoch_rootsift.m
2. Get a subset of keypoints to build codebook
sampling_feat4clustering_perdoch.m
sampling_feat4clustering_vgg_hesaff.m
3. Build codebooking using AKM
akm.py
4. Do quantization
quantize.m
quantize_check.m
quantize_merge.m

Execute multiple runs: 
processOneRun('run_configs/tv2013.surrey.soft.soft.latefusion.asym.cfg', 'tv2013', 'query2013', 'test2013', 10000); processOneRun('run_configs/tv2013.surrey.hard.soft.latefusion.asym.cfg', 'tv2013', 'query2013', 'test2013', 10000); processOneRun('run_configs/tv2013.perdoch.soft.soft.latefusion.asym.cfg', 'tv2013', 'query2013', 'test2013', 10000);