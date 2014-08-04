function conf = voc_config_9079()
	conf.pascal.year = '9079';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9079/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9079/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9079/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9079/Images/%s.txt';
end