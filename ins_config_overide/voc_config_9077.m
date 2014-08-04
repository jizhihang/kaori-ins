function conf = voc_config_9077()
	conf.pascal.year = '9077';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9077/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9077/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9077/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9077/Images/%s.txt';
end