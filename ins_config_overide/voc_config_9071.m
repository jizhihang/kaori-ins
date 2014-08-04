function conf = voc_config_9071()
	conf.pascal.year = '9071';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9071/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9071/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9071/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9071/Images/%s.txt';
end