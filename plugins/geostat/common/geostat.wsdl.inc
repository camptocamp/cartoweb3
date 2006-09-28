      <!-- general -->
      <complexType name="ArrayOfDecimal">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="xsd:decimal[]"/>
          </restriction>
        </complexContent>
      </complexType>
      
      <!-- themamap -->
      <complexType name="TwColorRgb">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="redLevel" type="xsd:integer"/>
          <element name="greenLevel" type="xsd:integer"/>
          <element name="blueLevel" type="xsd:integer"/>
        </all>
      </complexType>
      
      <complexType name="ArrayOfTwColorRgb">
        <complexContent>
          <restriction base="enc11:Array">
          <attribute ref="enc11:arrayType" 
            wsdl:arrayType="types:TwColorRgb[]"/>
          </restriction>
        </complexContent>
      </complexType>
      
      <complexType name="TwBin">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="label" type="xsd:string"/>
          <element name="nbVal" type="xsd:integer"/>
          <element name="lowerBound" type="xsd:decimal"/>
          <element name="upperBound" type="xsd:decimal"/>
          <element name="isLast" type="xsd:boolean"/>
        </all>
      </complexType>
      
      <complexType name="ArrayOfTwBin">
        <complexContent>
          <restriction base="enc11:Array">
          <attribute ref="enc11:arrayType" 
            wsdl:arrayType="types:TwBin[]"/>
          </restriction>
        </complexContent>
      </complexType>
      
      <complexType name="TwClassification">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="bins" type="types:ArrayOfTwBin"/>
        </all>
      </complexType>
      
      <complexType name="TwDistributionSummary">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="nbVal" type="xsd:integer"/>
          <element name="minVal" type="xsd:decimal"/>
          <element name="maxVal" type="xsd:decimal"/>
          <element name="meanVal" type="xsd:decimal"/>
          <element name="stdDevVal" type="xsd:decimal"/>
        </all>
      </complexType>
      
      <!-- geostat -->
      <complexType name="GeostatChoropleth">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="layer" type="xsd:string"/>
          <element name="indicator" type="xsd:string"/>
          <element name="bbox" type="types:Bbox"/>
          <element name="classificationMethod" type="xsd:integer"/>
          <element name="nbBins" type="xsd:integer"/>
          <element name="bounds" type="types:ArrayOfDecimal"/>
          <element name="labels" type="types:ArrayOfString"/>
          <element name="colorLutMethod" type="xsd:integer"/>
          <element name="colorInit" type="types:ArrayOfTwColorRgb"/>
          <element name="colors" type="types:ArrayOfTwColorRgb"/>
        </all>
      </complexType>
      
      <complexType name="GeostatRequest">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="status" type="xsd:boolean"/>
          <element name="choroplethParams" type="types:GeostatChoropleth"/>
        </all>
      </complexType>
      
      <complexType name="GeostatResult">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="choroplethDrawn" type="xsd:boolean"/>
          <element name="choroplethParams" type="types:GeostatChoropleth"/>
          <element name="choroplethClassification" 
            type="types:TwClassification"/>
          <element name="choroplethStats" 
            type="types:TwDistributionSummary"/>
        </all>
      </complexType>
      
      <complexType name="GeostatLayerParams">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="msLayer" type="xsd:string"/>
          <element name="label" type="xsd:string"/>
          <element name="choropleth" type="xsd:boolean"/>
          <element name="symbols" type="xsd:boolean"/>
          <element name="choropleth_attribs" type="xsd:string"/>
          <element name="choropleth_attribs_label" type="xsd:string"/>
          <element name="symbols_attribs" type="xsd:string"/>
          <element name="symbols_attribs_label" type="xsd:string"/>
        </all>
      </complexType>
      
      <complexType name="ArrayOfGeostatLayerParams">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" 
                wsdl:arrayType="types:GeostatLayerParams[]"/>
          </restriction>
        </complexContent>
      </complexType>
      
      <complexType name="GeostatInit">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="serverConfigParams" 
            type="types:ArrayOfGeostatLayerParams"/>
        </all>
      </complexType>
      
