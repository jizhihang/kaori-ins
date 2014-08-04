function conf = voc_config_9072()
	conf.pascal.year = '9072';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9072/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9072/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9072/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9072/Images/%s.txt';
end