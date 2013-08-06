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
