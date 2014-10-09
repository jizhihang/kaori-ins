function conf = voc_config_9099()
conf.pascal.year = '9099';
conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2014/model/ins-dpm/tv2014/query2014/9099/';
conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
conf.pascal.VOCopts.annopath = '/net/per610a/export/das11f/ledduy/trecvid-ins-2014/model/ins-dpm/tv2014/query2014/9099/Annotations/%s.txt';
conf.pascal.VOCopts.imgsetpath = '/net/per610a/export/das11f/ledduy/trecvid-ins-2014/model/ins-dpm/tv2014/query2014/9099/ImageSets/%s.txt';
conf.pascal.VOCopts.imgpath = '/net/per610a/export/das11f/ledduy/trecvid-ins-2014/model/ins-dpm/tv2014/query2014/9099/Images/%s.txt';
conf.pascal.VOCopts.datadir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2014/model/ins-dpm/tv2014/query2014/';
end
