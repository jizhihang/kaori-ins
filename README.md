kaori-ins
=========

KAORI-INS - A Framework for Instance Search

INS2014 Branch được sử dụng để tổ chức lại các code dùng cho TRECVID INS 2014 (01.08.2014)

I. Working Environment
1. GitHub path: https://github.com/ledduy/kaori-ins/tree/ins2014

2. Local dir: 
- (MacBook Pro): ˜/github-projects/kaori-ins  - Dùng GitHub for Mac để quản lí repository. Clone kaori-ins 

- (Desktop Windows): c:\Users\XXX\Documents\GitHub\kaori-ins - Dùng GitHub for Windows để quản lí repository. 

- LƯU Ý: Khi chọn branch ins2014 thì ở local thư mục kaori-ins sẽ chứa code của branch này, nếu đổi sang master thì code ở local cũng sẽ bị thay thế.

3. IDE: Eclipse-PHP - Dùng chức năng Import để Import as existing project. 
- (Desktop Windows): Đặt lại đường dẫn workspace về c:\Users\XXX\Documents\GitHub

- Chọn chức năng Import/Git/Projects from Git/Existing local repos/
- Chọn Add/C:\Users\ledduy\Documents\GitHub --> Import existing projects
- Khi cần Commit thì dùng tính năng Commit để cập nhật vào local repos. Rồi từ đó sync lên server.

4. Exec server: per9c/ledduy/github-projects/kaori-ins2014. Đơn giản là copy (clone, pull only).

II. Datasets
1. TV2013: 
- 5 keyframes/sec.
- Keyframe size: 768x576
- 471,526 shots --> 2,245,924 keyframes (dùng 5KF/shot cho DPM).
- shot0_xxx --> development, excluded from the test set.
- 30 topics (9069-9098)
- BBC EastEnders, approximately 244 video files (totally 300 GB, 464 h).
- Submission format: <item seqNum="1" shotId="shot4324_2" />

2. TV2014:
- 30 topics (9099-9128).
- 4 examples/topic.


III. Experiments
1. RootDir: @per610a/das11f/ledduy/trecvid-ins-2014

2. keyframe-5
2.1. tv2013
- tv2013/query2013: chép toàn bộ dữ liệu gồm có .src, .mask, .showmask của các query images (4 images/query), 3 formats gồm .bmp (gốc của TRECVID), .png, và .bmp.

- tv2013/test2013 (symlink -> -> /net/per610a/export/das11g/caizhizhu/ins/ins2013/frames_png/) - sames as tv2014/test2014
ln -s /net/per610a/export/das11g/caizhizhu/ins/ins2013/frames_png/ test2013

lrwxrwxrwx 1 ledduy users 60 Aug  4 11:15 test2013 -> /net/per610a/export/das11g/caizhizhu/ins/ins2013/frames_png/

- tv2013/test2013-new (symlink pointed to trecvid-ins-2013 (current dir is trecvid-ins-2014) -> /net/per610a/export/das11f/ledduy/trecvid-ins-2013/keyframe-5/tv2013/test2013-new/)
ln -s /net/per610a/export/das11f/ledduy/trecvid-ins-2013/keyframe-5/tv2013/test2013-new/ test2013-new

lrwxrwxrwx 1 ledduy users 82 Aug  4 11:17 test2013-new -> /net/per610a/export/das11f/ledduy/trecvid-ins-2013/keyframe-5/tv2013/test2013-new/

2.2. tv2014
- tv2014/test2014 (symlink -> /net/per610a/export/das11g/caizhizhu/ins/ins2013/frames_png/)
cd /net/per610a/export/das11f/ledduy/trecvid-ins-2014/keyframe-5/tv2014
ln -s /net/per610a/export/das11g/caizhizhu/ins/ins2013/frames_png/ test2014
lrwxrwxrwx  1 ledduy users   60 Aug  4 15:40 test2014 -> /net/per610a/export/das11g/caizhizhu/ins/ins2013/frames_png/

- tv2014/query2014: chép toàn bộ dữ liệu gồm có .src, .mask, .showmask của các query images (4 images/query), 3 formats gồm .bmp (gốc của TRECVID), .png, và .bmp. (*** Đã gây ra bug khi ct của T dùng lệnh scan dir để lấy toàn bộ các tập tin trong thư mục, dẫn đến việc gom hết các ảnh cả query và mask vào khi extract feature cho query, dẫn đến kết quả bị sai ***)

- tv2014/test2014-new: tạm thời ko dùng nữa.

3. metadata/keyframe-5
3.1. tv2013
- ins.topics.2013.xml --> danh sách các topics cung cấp bởi TRECVID (tên gốc là ins.auto.topics.2013.xml)
- ins.search.qrels.tv2013 --> groundtruth cung cấp bởi TRECVID (sau khi có kết quả)
- ins.search.qrels.tv2013.csv --> thông tin về số lượng relevant shots của từng query.

3.2. tv2014
- ins.topics.2014.xml --> danh sách các topics cung cấp bởi TRECVID (tên gốc là ins.auto.topics.2014.xml)
- ins.search.qrels.tv2014 --> groundtruth cung cấp bởi TRECVID (N/A - chỉ có sau khi có kết quả).
- ins.search.qrels.tv2014.csv --> thông tin về số lượng relevant shots của từng query (N/A - chỉ có sau khi có kết quả).

4. feature/keyframe-5
4.1. tv2013: cùng trỏ đến một thư mục của CZ
/net/per610a/export/das11f/ledduy/trecvid-ins-2014/feature/keyframe-5
[ledduy@per900a keyframe-5]$ ln -s /net/per610a/export/das11f/ledduy/plsang/nvtiep/INS/INS2013/hesaff_rootsift_noangle_mat/ tv2013/test2013/
[ledduy@per900a keyframe-5]$ ln -s /net/per610a/export/das11f/ledduy/plsang/nvtiep/INS/INS2013/hesaff_rootsift_noangle_cluster/ tv2013/test2013/
[ledduy@per900a keyframe-5]$ ln -s /net/per610a/export/das11f/ledduy/plsang/nvtiep/INS/INS2013/perdoch_hesaff_rootsift_cluster/ tv2013/test2013/
[ledduy@per900a keyframe-5]$ ln -s /net/per610a/export/das11f/ledduy/plsang/nvtiep/INS/INS2013/perdoch_hesaff_rootsift_mat/ tv2013/test2013/
lrwxrwxrwx 1 ledduy users  92 Aug  5 12:38 hesaff_rootsift_noangle_cluster -> /net/per610a/export/das11f/ledduy/plsang/nvtiep/INS/INS2013/hesaff_rootsift_noangle_cluster/
lrwxrwxrwx 1 ledduy users  88 Aug  5 12:37 hesaff_rootsift_noangle_mat -> /net/per610a/export/das11f/ledduy/plsang/nvtiep/INS/INS2013/hesaff_rootsift_noangle_mat/
lrwxrwxrwx 1 ledduy users  92 Aug  5 12:38 perdoch_hesaff_rootsift_cluster -> /net/per610a/export/das11f/ledduy/plsang/nvtiep/INS/INS2013/perdoch_hesaff_rootsift_cluster/
lrwxrwxrwx 1 ledduy users  88 Aug  5 12:38 perdoch_hesaff_rootsift_mat -> /net/per610a/export/das11f/ledduy/plsang/nvtiep/INS/INS2013/perdoch_hesaff_rootsift_mat/

4.1. tv2014: sử dụng lại feature của tv2013 - tạo symlink đến thư mục INS2013 của Tiep (thực ra là symlink đến thư mục của CZ cho hesaff_rootsift và perdoch_rootsift).
[ledduy@per900a keyframe-5]$ pwd
/net/per610a/export/das11f/ledduy/trecvid-ins-2014/feature/keyframe-5
[ledduy@per900a keyframe-5]$ ln -s /net/per610a/export/das11f/ledduy/plsang/nvtiep/INS/INS2013/hesaff_rootsift_noangle_cluster/ tv2014/test2014/
[ledduy@per900a keyframe-5]$ ln -s /net/per610a/export/das11f/ledduy/plsang/nvtiep/INS/INS2013/hesaff_rootsift_noangle_mat/ tv2014/test2014/
[ledduy@per900a keyframe-5]$ ln -s /net/per610a/export/das11f/ledduy/plsang/nvtiep/INS/INS2013/perdoch_hesaff_rootsift_mat/ tv2014/test2014/
[ledduy@per900a keyframe-5]$ ln -s /net/per610a/export/das11f/ledduy/plsang/nvtiep/INS/INS2013/perdoch_hesaff_rootsift_cluster/ tv2014/test2014/
lrwxrwxrwx 1 ledduy users  92 Aug  5 12:35 hesaff_rootsift_noangle_cluster -> /net/per610a/export/das11f/ledduy/plsang/nvtiep/INS/INS2013/hesaff_rootsift_noangle_cluster/
lrwxrwxrwx 1 ledduy users  88 Aug  5 12:36 hesaff_rootsift_noangle_mat -> /net/per610a/export/das11f/ledduy/plsang/nvtiep/INS/INS2013/hesaff_rootsift_noangle_mat/
lrwxrwxrwx 1 ledduy users  92 Aug  5 12:36 perdoch_hesaff_rootsift_cluster -> /net/per610a/export/das11f/ledduy/plsang/nvtiep/INS/INS2013/perdoch_hesaff_rootsift_cluster/
lrwxrwxrwx 1 ledduy users  88 Aug  5 12:36 perdoch_hesaff_rootsift_mat -> /net/per610a/export/das11f/ledduy/plsang/nvtiep/INS/INS2013/perdoch_hesaff_rootsift_mat/

5. model/ins-dpm
5.1. tv2013/query2013/90xx
Các tập tin sau được sinh ra từ chương trình nmduc/prepare_annotation_for_dpm (và validate_mask.m)
- 90xx.cfg: config file (chứa thông tin về scale factor, số lượng ảnh positives, số lượng ảnh bị discarded vì mask size nhỏ - tính bởi hàm validate_mask.m)

- Images: list of query images (90xx.z.src.png) - danh sách các query images được dùng cho training (có bounding box được ghi tương ứng trong file annotation, 1 ảnh có thể có nhiều bounding box, ví dụ 9071
Các ảnh positive sẽ được phóng to so với ảnh gốc rồi mới copy vào thư mục này nếu scale factor > 1.
Lưu ý rằng các neg-images sẽ được copy cho từng query (mặc định là các query dùng chung neg image set), chúng được đặt ta keyframe-5/tv2013/query2013-neg-images

- ImageSets: metadata for pos set (trainval.txt) and neg set (train.txt) - tên của pos set và neg set được định nghĩa trong voc_config.m (conf = cv(conf, 'training.train_set_fg', 'trainval');
conf = cv(conf, 'training.train_set_bg', 'train');). Mỗi dòng của tập tin này chứa tên một tập tin của tập tương ứng (pos/neg), ko có đường dẫn, ko có .ext

- Annotations: annotation is PASCAL-VOC format - các annotation này được tính toán tự động dựa vào mask image và ghi ra theo format của PASCAL-VOC (validate_mask.m)
Lưu ý quan trọng là mỗi tập tin của tập pos và tập neg, đều phải có tập tin annotation .txt tương ứng. 
Với tập tin ảnh của tập neg, đơn giản chỉ có một dòng là đường dẫn đến tên tập tin. Ví dụ: 9069/Images/neg-1.jpg
Với tập tin của tập pos, thì ngoài đường dẫn đến tên tập tin (ví dụ 9069/Images/9069.1.src.jpg), còn có thông tin của bounding box và CLASS (ví dụ inriaperson, airplane, dog) tương ứng với nó. CLASS này sẽ được dùng để so sánh với cls của hàm train_model.m và lưu ý là phải viết THƯỜNG hoàn toàn (ví dụ query_9069)

- Khi chạy, chương trình sẽ lấy thông tin của đường dẫn từ gốc và ghép với đường dẫn tương đối này. Đường dẫn từ gốc được xác định bởi VOCopts.datadir.
Ví dụ: VOCopts.datadir = /net/per610a/export/das11f/ledduy/trecvid-ins-2014/model/ins-dpm/tv2013/query2013/

- voc_config_9069.m: tập tin lưu các thông tin của biến VOCopts, trong đó chỉ định đường dẫn của model_dir, datadir, etc
function conf = voc_config_9069()
conf.pascal.year = '9069';
conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2014/model/ins-dpm/tv2013/query2013/9069/';
conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
conf.pascal.VOCopts.annopath = '/net/per610a/export/das11f/ledduy/trecvid-ins-2014/model/ins-dpm/tv2013/query2013/9069/Annotations/%s.txt';
conf.pascal.VOCopts.imgsetpath = '/net/per610a/export/das11f/ledduy/trecvid-ins-2014/model/ins-dpm/tv2013/query2013/9069/ImageSets/%s.txt';
conf.pascal.VOCopts.imgpath = '/net/per610a/export/das11f/ledduy/trecvid-ins-2014/model/ins-dpm/tv2013/query2013/9069/Images/%s.txt';
conf.pascal.VOCopts.data_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2014/model/ins-dpm/tv2013/query2013/';
end

HOW TO USE DPM
1. Download DPV v5 and extract to voc-release5 (/net/per900c/raid0/ledduy/github-projects/kaori-ins2014/voc-release5/) (http://people.cs.uchicago.edu/~rbg/latent/voc-release5.tgz)

2. Download Pascal VOC devkit 2012 (http://pascallin.ecs.soton.ac.uk/challenges/VOC/voc2012/VOCdevkit_18-May-2011.tar) from http://pascallin.ecs.soton.ac.uk/challenges/VOC/voc2012/index.html

3. Untar VOCdevkit_18-May-2011.tar to voc-release5/INS-DPM/VOCdevkit
(i.e. you should have the folder voc-release5/INS-DPM/VOCcode by now)

4. Edit VOCinit.m under INS-DPM/VOCdevkit/VOCcode

5. Edit voc_config.m under voc-release5/

voc_config.m
*** BASE_DIR    = '/net/per900c/raid0/ledduy/github-projects/kaori-ins2014/voc-release5/'; - đây là code của DPM-ver5.0 tải về và giải nén

*** PASCAL_YEAR = 'QueryID'; % this var will be changed to 9069 in voc_config_9069.m  - do mỗi model của mỗi query được lưu trong từng thư mục riêng, ví dụ model/ins-dpm/tv2013/query2013/9069 nên sẽ phải dùng voc_config_9069.m để override biến này (trước khi chạy train_model, phải chạy: global VOC_CONFIG_OVERRIDE; VOC_CONFIG_OVERRIDE = @voc_config_9069;). 
Trong khi đó, với PASCAL_VOC, các model của các category đều đặt chung một thư mục, ví dụ như voc-release5/VOC2007 hay voc-release5/VOC2010

*** PROJECT     = 'INS-DPM'; % dir containing VOCdevkit downloaded from PASCAL-VOC site --> hard-coded later conf = cv(conf, 'pascal.dev_kit', [conf.paths.base_dir '/INS-DPM/VOCdevkit/']);
% The code will look for your PASCAL VOC devkit in 
% BASE_DIR/VOC<PASCAL_YEAR>/VOCdevkit
% e.g., /var/tmp/rbg/VOC2007/VOCdevkit
% If you have the devkit installed elsewhere, you may want to 
% create a symbolic link.

******* STEPS **********

1. Chạy prepare_annotation_for_dpm('tv2013', 'query2013')

2. Chạy nmduc/train_models_tv2013.sh on bc3x (grid)

5.2. tv2014/query2014/90xx - tương tự tv2013

IV. Diary
*** 03Aug2014 ***
1. Tạo môi trường làm việc trên MacBookPro, Desktop Windows, và Server

2. Tạo thư mục RootDir và các thư mục con. Đặt quyền 77x.

3. Cập nhật các tập tin cho KAORI-INS app config như là ksc-AppConfig.php, etc.

4. Xóa các tập tin dùng BoW (từ hệ thống KAORI-SECODE).

5. Website:  http://per900c.hpc.vpl.nii.ac.jp/users-ext/ledduy//www/kaori-ins2014/
[ledduy@per900a www]$ pwd
/net/per610a/export/das09f/satoh-lab/ledduy/www

ln -s /net/per900c/raid0/ledduy/github-projects/kaori-ins2014/ kaori-ins2014

6. Cập nhật ksc-web-ViewResult.php
- Query images tạm thời ko dùng metadata mà collect bằng scan dir.

*** 04Aug2014 - 06Aug2014
1. Code lại processOneRun với cấu trúc thư mục mới và debug.

2. Chỉnh lại code của các trang web vì cấu trúc thư mục thay đổi (runID/tv2013/test2013 --> tv2013/test2013/runID)

*** 07Aug2014
1. Chỉnh code của DPM để chạy



*** 13 Aug 2014

1. Chạy fusion R1_tv2013.fusion-surrey.hard.soft+DPM[2-1] --> surrey.hard.soft + DPM.surrey.hard.soft (dùng web ksc-web-LateFusion4MultiRuns)
Fusion run config
NormScoreMethod: Z-Score by Using Mean and Std
TVYear: tv2014
Output Name: R1_tv2013.fusion-surrey.hard.soft+DPM[2-1]
R0: R2_tv2013.surrey.hard.soft.latefusion.asym_fg+bg_0.1_hesaff_rootsift_noangle_akmeans_1000000_100000000_50_kdtree_8_800_v1_f1_1_avg_pooling_full_notrim_clip_idf_nonorm_kdtree_3_0.0125_-1_dist_avg_autoasym_ivf_0.5 - Weight: 2.00
R1: R3_tv2013.DPM.surrey.hard.soft.latefusion.asym_fg+bg_0.1_hesaff_rootsift_noangle_akmeans_1000000_100000000_50_kdtree_8_800_v1_f1_1_avg_pooling_full_notrim_clip_idf_nonorm_kdtree_3_0.0125_-1_dist_avg_autoasym_ivf_0.5 - Weight: 1.00
<P>QueryID = 9099 - Mean = 0.1159 - Std = 0.0151 - Path: /net/per610a/export/das11f/ledduy/trecvid-ins-2014/result/tv2014/test2014/R2_tv2013.surrey.hard.soft.latefusion.asym_fg+bg_0.1_hesaff_rootsift_noangle_akmeans_1000000_100000000_50_kdtree_8_800_v1_f1_1_avg_pooling_full_notrim_clip_idf_nonorm_kdtree_3_0.0125_-1_dist_avg_autoasym_ivf_0.5/9099<BR>

2. Chạy fusion của Tiệp --> R0_tv2013.surrey.hard.soft+DPM+RANSAC

3. Chạy ksc-ProcessOneRun-Rank.php 2014 R0_tv2013.surrey.hard.soft+DPM+RANSAC --> để sinh ra các file .rank

4. Chạy ksc-Tool-Convert2NISTSubmissionFormat-tv2014.php --> .xml lưu ở NISTSubmission và R0_tv2013.surrey.hard.soft+DPM+RANSAC (đòi hỏi file .rank phải có trước)
