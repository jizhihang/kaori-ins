kaori-ins
=========

KAORI-INS - A Framework for Instance Search

INS2014 Branch được sử dụng để tổ chức lại các code dùng cho TRECVID INS 2014 (01.08.2014)

I. Working Environment
1. GitHub path: https://github.com/ledduy/kaori-ins/tree/ins2014

2. Local dir: ˜/github-projects/kaori-ins (MacBook Pro) - Dùng GitHub for Mac để quản lí repository. Clone kaori-ins 

3. IDE: Eclipse-PHP - Dùng chức năng Import để Import as existing project. 
Khi cần Commit thì dùng tính năng Commit để cập nhật vào local repos. Rồi từ đó sync lên server.

4. Exec server: per9c/ledduy/github-projects/kaori-ins2014. Đơn giản là copy (clone, pull only).

II. Datasets
1. TV2014:
- 30 topics (9099-9128).
- 4 examples/topic.

2. TV2013: 
- 5 keyframes/sec.
- Keyframe size: 768x576
- 471,526 shots --> 2,245,924 keyframes (dùng 5KF/shot cho DPM).
- shot0_xxx --> development, excluded from the test set.
- 30 topics (9069-9098)
- BBC EastEnders, approximately 244 video files (totally 300 GB, 464 h).
- Submission format: <item seqNum="1" shotId="shot4324_2" />

III. Experiments
1. RootDir: @per610a/das11f/ledduy/trecvid-ins-2014

IV. Diary
*** 03Aug2014 ***
1. Tạo thư mục RootDir và các thư mục con

2. Cập nhật các tập tin cho KAORI-INS app config như là ksc-AppConfig.php, etc.

3. Xóa các tập tin dùng BoW (từ hệ thống KAORI-SECODE).
