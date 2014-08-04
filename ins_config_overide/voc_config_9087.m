function conf = voc_config_9087()
	conf.pascal.year = '9087';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9087/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9087/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9087/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9087/Images/%s.txt';
end