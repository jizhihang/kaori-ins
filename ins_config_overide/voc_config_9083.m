function conf = voc_config_9083()
	conf.pascal.year = '9083';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9083/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9083/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9083/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9083/Images/%s.txt';
end