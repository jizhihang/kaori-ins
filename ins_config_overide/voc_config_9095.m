function conf = voc_config_9095()
	conf.pascal.year = '9095';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9095/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9095/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9095/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9095/Images/%s.txt';
end