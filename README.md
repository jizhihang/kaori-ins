kaori-ins
=========

KAORI-INS - A Framework for Instance Search

1. IDE Setup
- Download Eclipse PDT: http://www.zend.com/en/company/community/pdt/downloads (Zend Eclipse PDT  PDT 3.2.0 w/Eclipse Indigo	(ZIP) 127.69 MB)
- Install on D:\zend-eclipse-php
- Install EGit: Eclipse/Help/Install New Software --> Paste link: http://download.eclipse.org/egit/updates (ref: http://www.eclipse.org/egit/download/)

2. New repository
- Create new repository kaori-ins: https://github.com/ledduy/kaori-ins.git

3. Clone 
+ to local D:\ for coding
- Ref:  http://wiki.eclipse.org/EGit/User_Guide#Working_with_remote_Repositories
- Eclipse/Import
+ to server V:\ (per900b/raid0/ledduy)
cd /net/per900b/raid0/ledduy/github-projects
git clone https://github.com/ledduy/kaori-ins.git

4. Datasets
- TV2011: 
+ 3 keyframes/sec
+ Keyframe size; 352x288
+ 20,982 clips --> 1,650,827 keyframes 
+ 25 topics (9023-9047)
+ All videos were chopped into 20 to 10s clips using ffmpeg
+ ~ 100 hours, BBC rushes
+ Submission format: <item seqNum="1" shotId="8123"/>

- TV2012
+ 3 keyframes/sec
+ 33 keyframes/clip
+ 74,958 clips --> 2,256,930 
+ 21 topics (9048-9068)
+ Keyframe size: 640x480
+ Flickr video
+ Submission format: <item seqNum="1" shotId="FL000000001"/>

- TV2013: 
+ 5 keyframes/sec
+ Keyframe size: 768x576
+ 471,526 shots --> 2,245,924 keyframes
+ 30 topics (9069-9098)
+ BBC EastEnders, approximately 244 video files (totally 300 GB, 464 h)
+ Submission format: <item seqNum="1" shotId="shot4324_2" />

5. Steps
5.1. Generate metadata (DONE Aug 12)
- Code: php -f ksc-Tool-GenerateMetaData.php 2011|2012|2013 test|query|queryext50 
- Generate metadata for new corresponding pats, e.g. test2013-new
- For tv2011, tv2012 --> copy data to keyframe-5/tv2012/test2012-new/TRECVID2012_1/*.tar
- For tv2013 --> only 5KF/shot are copied and packed in .tar file.
- Running time on SGE (24 cores) for tv2013 is 16 hours, tv2012 & tv2011 is 4 hours.

5.2. Generate metadata for subtest --> only subtest2012-new
- Code: ksc-Tool-Tool-GenerateMetaDataForSubTest.php 2012
- Note: Copy *.tar files of selected shots to new dir (it is better to use softlink, but here cp is used).
- Shot sampling rate: $arSamplingRateList = array(2011 => 10, 2012 => 20, 2013 => 20);
- subtest2012-new: 
+ #keyframes: 146,966 (full:  2,256,930)
+ #shots: ~98x50
+ note: shots of groundtruth are also added. 

5.2. Extract raw local features using colorsift
- Code: ksc-Feature-ExtractRawAffCovSIFTFeature-COLORSIFT*.*
- Max image size: 500x500 (specified in ksc-AppConfig.php)
- Keypoint detector: HarLap & Dense sampling (step size 6 pixel, 2 scales).
- Descriptor: SIFT and rgbSIFT.

***colordescriptor ver 3.0 - Processing time: 
- harlap x rgbsift: 12 sec/KF
- harlap x sift: 7 sec/KF
- dense6 x rgbsift: 10 sec/KF
- dense6 x sift: 6 sec/KF
- note: colordescriptor auto resize large KF into max 500x500, and CPU is usually 2x

5.3. Running DPM model
- tv2012 ~ 4 sec/keyframe 
- tv2013: 16 sec/keyframe for 2x scale factor on test keyframes, 6 sec/keyframe for normal KF.


*************************** RE-TEST THE FRAMEWORK **********************

6.1. Raw feature extraction
- Only dense6mul.sift is used for test2012-new
- Pat are defined in ksc-AppConfigForProject.php
- Total time: 6*2.25M/3600 = 3,750 hours (Aug23-23:30 --> [Aug25-->10:00 - Last jobs!])
- 56 cores until Aug25-03:00AM, 550 cores after that (INS deadline)

- test2011-new
- Aug26-->23:55 ==> Aug27-->9:55 - 1,000jobs*90mins/job = 1,500 hours 
(max 280 cores --> 150 cores because colordescriptor requires 1.5-2.0 CPUs 
*** Clustering
- Aug27-->10:10 ==> Aug27-->12:00

6.2. Quantization
- 1K codebook of subtest2012-new
- Total time: 6.5*2.25/3600 = 4,100 hours --> 20 hours (max 200 cores) 
--> [Aug25-->23:15 - Aug25-->16:27 - Last jobs! 280 cores]

- test2011-new
Aug27-->16:45 ==> Aug28-->05:00

6.3. Matching
- Total time: 2,100 jobs (21 queries) x 30 mins/job --> 1,050 hours
--> [Aug26--> 16:45 --> Aug26-->20:45 280 cores]

6.4. Ranking and Selecting for DPM

6.5. Running DPM

6.6. Fusion and Evaluation

