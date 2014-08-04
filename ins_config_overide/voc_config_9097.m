function conf = voc_config_9097()
	conf.pascal.year = '9097';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9097/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9097/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9097/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9097/Images/%s.txt';
end