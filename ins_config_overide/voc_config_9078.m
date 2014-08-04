function conf = voc_config_9078()
	conf.pascal.year = '9078';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9078/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9078/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9078/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9078/Images/%s.txt';
end