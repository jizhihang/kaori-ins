function conf = voc_config_9086()
	conf.pascal.year = '9086';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9086/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9086/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9086/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9086/Images/%s.txt';
end